<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Http\Requests\QuoteRequest;
use App\Models\Address;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceExtra;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\StripeService;
use App\Support\BookingView;
use App\Support\Notify;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** The public multi-step booking wizard and its JSON endpoints. */
class BookingController extends Controller
{
    public function __construct(
        private PricingService $pricing,
        private AvailabilityService $availability,
        private BookingService $bookings,
    ) {}

    public function wizard(Request $request): Response
    {
        $user = $request->user();
        $pre = $request->query('service');

        return Inertia::render('Booking/Wizard', [
            'services' => Service::active()->with('category')->orderBy('sort_order')->get()->map(fn ($s) => [
                'id' => $s->id, 'slug' => $s->slug, 'name' => $s->name, 'short_description' => $s->short_description,
                'image_url' => $s->image_url, 'base_price' => (float) $s->base_price, 'pricing_method' => $s->pricing_method,
                'duration_minutes' => $s->duration_minutes, 'category' => $s->category?->name,
            ]),
            'extras' => ServiceExtra::where('is_active', true)->orderBy('sort_order')
                ->get(['id', 'name', 'description', 'price', 'duration_minutes']),
            'propertyTypes' => collect(config('freshnest.property_types'))->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'frequencies' => collect(config('freshnest.frequencies'))->map(fn ($l, $k) => [
                'key' => $k, 'label' => $l,
                'discount' => match ($k) {
                    'weekly' => Setting::float('discount_weekly'),
                    'biweekly' => Setting::float('discount_biweekly'),
                    'monthly' => Setting::float('discount_monthly'),
                    default => 0,
                },
            ])->values(),
            'paymentMethods' => collect(BookingRequest::enabledMethods())->map(fn ($m) => ['key' => $m, 'label' => config('freshnest.payment_methods')[$m]])->values(),
            'stripeEnabled' => StripeService::enabled(),
            'preselectedServiceId' => $pre ? Service::active()->where('slug', $pre)->value('id') : null,
            'minDate' => now()->addHours(Setting::int('min_notice_hours', 12))->format('Y-m-d'),
            'maxDate' => now()->addDays(Setting::int('max_advance_days', 60))->format('Y-m-d'),
            'idempotencyKey' => (string) Str::uuid(),
            'savedAddresses' => $user ? $user->addresses()->orderByDesc('is_default')->get(['id', 'label', 'street', 'unit', 'city', 'state', 'zip', 'country', 'country_code', 'latitude', 'longitude', 'instructions']) : [],
            'prefill' => $user ? ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone] : null,
            'cancellationHours' => Setting::int('cancellation_hours', 24),
        ]);
    }

    /** Live, server-computed price. */
    public function quote(QuoteRequest $request): JsonResponse
    {
        return response()->json($this->pricing->quoteForClient($request->validated()));
    }

    /** Available start times for one day, based on the job's computed duration. */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate(array_merge((new QuoteRequest)->rules(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]));

        $quote = $this->pricing->quoteForClient($data);
        $slots = $this->availability->slotsFor(Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay(), $quote['duration_minutes']);

        return response()->json(['date' => $data['date'], 'duration_minutes' => $quote['duration_minutes'], 'slots' => $slots]);
    }

    public function store(BookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Service areas are matched by postal code when one is listed, otherwise by city name. An admin can switch the check off.
        if (Setting::bool('restrict_service_area') && ! ServiceArea::serves($data['zip'] ?? null, $data['city'])) {
            throw ValidationException::withMessages([filled($data['zip'] ?? null) ? 'zip' : 'city' => 'Sorry, we do not currently serve this location. Please contact us.']);
        }

        $user = $request->user();

        if (! $user) {
            if (User::where('email', strtolower($data['contact_email']))->exists()) {
                throw ValidationException::withMessages(['contact_email' => 'An account with this email already exists. Please sign in to continue.']);
            }
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => strtolower($data['contact_email']),
                'phone' => $data['contact_phone'],
                'password' => $data['password'],
            ]);
            $user->forceFill(['role' => 'customer', 'is_active' => true])->save();
            $user->sendVerificationSafely();
            Notify::user($user, 'welcome', ['name' => $user->name]);
            Auth::login($user);
            $request->session()->regenerate();
        }

        $booking = $this->bookings->create($data, $user);

        if ($user->addresses()->count() === 0) {
            $user->addresses()->create([
                'label' => 'Home', 'street' => $data['street'], 'unit' => $data['unit'] ?? null,
                'city' => $data['city'], 'state' => $data['state'] ?? null, 'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? null, 'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null,
                'instructions' => $data['instructions'] ?? null, 'is_default' => true,
            ]);
        }

        $response = ['reference' => $booking->reference, 'redirect' => route('booking.confirmation', $booking)];

        if ($booking->payment_method === 'stripe' && ! $booking->requires_quote && StripeService::enabled()) {
            try {
                $response['checkout_url'] = app(StripeService::class)->checkoutUrl($booking->load('service'));
            } catch (\Throwable $e) {
                report($e);
                $response['payment_notice'] = 'Online payment is unavailable right now. Your booking is saved and we will contact you about payment.';
            }
        }

        return response()->json($response, 201);
    }

    public function confirmation(Booking $booking): Response
    {
        Gate::authorize('viewAsCustomer', $booking);

        return Inertia::render('Booking/Confirmation', [
            'booking' => BookingView::detail($booking, auth()->user()),
        ]);
    }
}
