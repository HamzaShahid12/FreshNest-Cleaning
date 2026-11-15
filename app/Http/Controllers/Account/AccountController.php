<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Booking;
use App\Models\ContactEnquiry;
use App\Models\Invoice;
use App\Models\Review;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Support\BookingView;
use App\Support\Notify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

/** Customer portal. Every query is scoped to the signed-in customer. */
class AccountController extends Controller
{
    public function __construct(private BookingService $bookings, private AvailabilityService $availability) {}

    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $mine = Booking::where('user_id', $user->id);

        return Inertia::render('Customer/Dashboard', [
            'stats' => [
                'total_bookings' => (clone $mine)->count(),
                'upcoming' => (clone $mine)->active()->where('scheduled_at', '>=', now())->count(),
                'completed' => (clone $mine)->where('status', 'completed')->count(),
                'total_spent' => (float) (clone $mine)->where('status', 'completed')->sum('total'),
            ],
            'upcoming' => (clone $mine)->with(['service', 'employee'])->active()->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')->limit(5)->get()->map(fn ($b) => BookingView::summary($b)),
            'recent' => (clone $mine)->with(['service', 'employee'])->where('scheduled_at', '<', now())
                ->orderByDesc('scheduled_at')->limit(5)->get()->map(fn ($b) => BookingView::summary($b)),
            'needsReview' => (clone $mine)->with('service')->where('status', 'completed')->doesntHave('review')
                ->latest('scheduled_at')->limit(3)->get()->map(fn ($b) => BookingView::summary($b)),
            'addressCount' => $user->addresses()->count(),
        ]);
    }

    public function bookings(Request $request): Response
    {
        $tab = in_array($request->query('tab'), ['upcoming', 'past', 'all'], true) ? $request->query('tab') : 'all';

        $query = Booking::with(['service', 'employee'])->where('user_id', $request->user()->id)
            ->when($tab === 'upcoming', fn ($q) => $q->active()->where('scheduled_at', '>=', now())->orderBy('scheduled_at'))
            ->when($tab === 'past', fn ($q) => $q->where(fn ($w) => $w->where('scheduled_at', '<', now())->orWhereNotIn('status', config('freshnest.active_statuses')))->orderByDesc('scheduled_at'))
            ->when($tab === 'all', fn ($q) => $q->orderByDesc('scheduled_at'));

        return Inertia::render('Customer/Bookings/Index', [
            'bookings' => $query->paginate(10)->withQueryString()->through(fn ($b) => BookingView::summary($b)),
            'tab' => $tab,
        ]);
    }

    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('viewAsCustomer', $booking);

        return Inertia::render('Customer/Bookings/Show', [
            'booking' => BookingView::detail($booking, $request->user()),
            'cancellationHours' => \App\Models\Setting::int('cancellation_hours', 24),
            'minDate' => now()->format('Y-m-d'),
        ]);
    }

    /** Free slots for rescheduling this booking (same duration, excluding itself). */
    public function slots(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('viewAsCustomer', $booking);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return response()->json([
            'date' => $data['date'],
            'slots' => $this->availability->slotsFor(Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay(), $booking->duration_minutes, $booking->id),
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('viewAsCustomer', $booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        if (! $booking->customerCanModify()) {
            return back()->with('error', 'This booking is inside the cancellation window. Please contact us to make changes.');
        }

        $this->bookings->transition($booking, 'cancelled', $request->user(), $data['reason'] ?? 'Cancelled by customer');

        return back()->with('success', 'Your booking has been cancelled.');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('viewAsCustomer', $booking);
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        $this->bookings->reschedule($booking, $data['date'], $data['time'], $request->user(), byCustomer: true);

        return back()->with('success', 'Your booking has been rescheduled.');
    }

    public function review(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('viewAsCustomer', $booking);
        abort_unless($booking->status === 'completed', 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($booking->review()->exists()) {
            return back()->with('error', 'You have already reviewed this booking.');
        }

        Review::create($data + [
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'service_id' => $booking->service_id,
            'is_approved' => false, // moderated by an admin before it appears publicly
        ]);

        return back()->with('success', 'Thank you! Your review will appear after approval.');
    }

    /* ------------------------------ Addresses ----------------------------- */

    public function addresses(Request $request): Response
    {
        return Inertia::render('Customer/Addresses', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    private function addressRules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'street' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]{1,19}$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
        ];
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate($this->addressRules());
        $user = $request->user();

        if (! empty($data['is_default']) || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }
        $user->addresses()->create($data);

        return back()->with('success', 'Address saved.');
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $request->validate($this->addressRules());

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        $address->update($data);

        return back()->with('success', 'Address updated.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    /* ------------------------------ Invoices ------------------------------ */

    public function invoices(Request $request): Response
    {
        return Inertia::render('Customer/Invoices', [
            'invoices' => Invoice::with('booking.service')
                ->whereHas('booking', fn ($q) => $q->where('user_id', $request->user()->id))
                ->latest('issued_on')->latest('id')->paginate(15)->through(fn ($i) => [
                    'id' => $i->id, 'number' => $i->number, 'issued_on' => $i->issued_on->format('M j, Y'),
                    'total' => (float) $i->total, 'status' => $i->status,
                    'booking_reference' => $i->booking->reference, 'service' => $i->booking->service?->name,
                ]),
        ]);
    }

    /* ------------------------------ Profile ------------------------------- */

    public function profile(Request $request): Response
    {
        $u = $request->user();

        return Inertia::render('Customer/Profile', [
            'profile' => ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'verified' => $u->hasVerifiedEmail(), 'avatar_url' => $u->avatarUrl()],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $emailChanged = strtolower($data['email']) !== strtolower($user->email);
        $user->fill($data);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $request->user()->forceFill(['password' => $data['password']])->save();

        return back()->with('success', 'Password changed.');
    }

    /* ------------------------------ Support ------------------------------- */

    public function support(Request $request): Response
    {
        return Inertia::render('Customer/Support', [
            'bookings' => Booking::where('user_id', $request->user()->id)->latest('scheduled_at')->limit(20)->get(['reference'])->pluck('reference'),
        ]);
    }

    public function sendSupport(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'booking_reference' => ['nullable', 'string', 'max:30'],
        ]);

        $subject = $data['subject'].(filled($data['booking_reference'] ?? null) ? ' ['.$data['booking_reference'].']' : '');
        $enquiry = ContactEnquiry::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
            'subject' => $subject, 'message' => $data['message'], 'status' => 'new',
        ]);
        Notify::admins('admin_contact_received', ['name' => $enquiry->name, 'message' => $enquiry->message], route('admin.resource.index', 'enquiries'));

        return back()->with('success', 'Message sent. Our team will reply by email.');
    }
}
