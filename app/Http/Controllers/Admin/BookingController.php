<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingCalculator;
use App\Services\StripeService;
use App\Support\BookingView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings, private AvailabilityService $availability) {}

    public function index(Request $request): Response
    {
        $f = $request->only(['search', 'status', 'service', 'from', 'to']);

        $query = Booking::with(['service', 'employee'])
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['service'] ?? null, fn ($q, $v) => $q->where('service_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('scheduled_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('scheduled_at', '<=', $v))
            ->when($f['search'] ?? null, function ($q, $v) {
                $q->where(fn ($w) => $w->where('reference', 'like', "%{$v}%")
                    ->orWhere('contact_name', 'like', "%{$v}%")
                    ->orWhere('contact_email', 'like', "%{$v}%")
                    ->orWhere('contact_phone', 'like', "%{$v}%"));
            })
            ->orderByDesc('scheduled_at');

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $query->paginate(15)->withQueryString()->through(fn ($b) => BookingView::summary($b) + ['customer' => $b->contact_name]),
            'filters' => $f + ['search' => $f['search'] ?? '', 'status' => $f['status'] ?? '', 'service' => $f['service'] ?? '', 'from' => $f['from'] ?? '', 'to' => $f['to'] ?? ''],
            'services' => Service::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Booking $booking): Response
    {
        $staff = User::where('role', 'staff')->where('is_active', true)->with('employeeProfile')->orderBy('name')->get()
            ->filter(fn ($u) => ! $u->employeeProfile || $u->employeeProfile->canDoService($booking->service_id))
            ->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name,
                'free' => $this->availability->employeeIsFree($u, $booking->scheduled_at, $booking->ends_at, $booking->id),
            ])->values();

        $detail = BookingView::detail($booking, $request->user());
        $detail['transitions'] = config("freshnest.transitions.{$booking->status}", []);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $detail,
            'staff' => $staff,
            'minDate' => now()->format('Y-m-d'),
            'payment' => $booking->payments()->latest('id')->first(['id', 'status', 'method', 'amount', 'refund_amount']),
        ]);
    }

    public function print(Request $request, Booking $booking): Response
    {
        return Inertia::render('Admin/Bookings/Print', ['booking' => BookingView::detail($booking, $request->user())]);
    }

    public function status(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(config('freshnest.statuses')))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->bookings->transition($booking, $data['status'], $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Booking status updated.');
    }

    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['employee_id' => ['required', 'integer', 'exists:users,id']]);
        $employee = User::findOrFail($data['employee_id']);

        $this->bookings->assign($booking, $employee, $request->user());

        return back()->with('success', "Assigned to {$employee->name}.");
    }

    public function slots(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return response()->json([
            'slots' => $this->availability->slotsFor(Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay(), $booking->duration_minutes, $booking->id),
        ]);
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        $this->bookings->reschedule($booking, $data['date'], $data['time'], $request->user(), byCustomer: false);

        return back()->with('success', 'Booking rescheduled.');
    }

    public function notes(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['work_notes' => ['nullable', 'string', 'max:4000']]);
        $booking->update(['work_notes' => $data['work_notes']]);

        return back()->with('success', 'Notes saved.');
    }

    /** Record an offline payment (cash / bank / invoice) against a booking. */
    public function recordPayment(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['reference' => ['nullable', 'string', 'max:120']]);

        $payment = $booking->payments()->where('status', 'pending')->latest('id')->first();
        if (! $payment) {
            throw ValidationException::withMessages(['reference' => 'There is no pending payment on this booking.']);
        }
        if ($payment->provider === 'stripe') {
            throw ValidationException::withMessages(['reference' => 'Stripe payments are confirmed automatically by the Stripe webhook.']);
        }
        if ($booking->requires_quote && (float) $booking->total <= 0) {
            throw ValidationException::withMessages(['reference' => 'Set the quoted amount before recording a payment.']);
        }

        $this->bookings->recordPayment($payment, $data['reference'] ?? 'MANUAL-'.strtoupper(substr(md5((string) microtime(true)), 0, 8)));

        return back()->with('success', 'Payment recorded.');
    }

    /** Set the final price for a custom-quote booking (e.g. office cleaning). */
    public function setQuote(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->requires_quote, 422, 'This booking has a fixed price.');
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:100000']]);

        $total = round((float) $data['amount'], 2);
        $booking->update(['subtotal' => $total, 'total' => $total, 'discount_total' => 0, 'tax' => 0]);
        $booking->invoice()->update(['subtotal' => $total, 'discount' => 0, 'tax' => 0, 'total' => $total]);
        $booking->payments()->where('status', 'pending')->update(['amount' => $total]);

        return back()->with('success', 'Quote saved.');
    }

    public function refund(Request $request, Booking $booking): RedirectResponse
    {
        $payment = $booking->payments()->whereIn('status', ['paid', 'partially_refunded'])->latest('id')->first();
        if (! $payment) {
            throw ValidationException::withMessages(['amount' => 'There is no paid payment to refund.']);
        }

        $max = round((float) $payment->amount - (float) $payment->refund_amount, 2);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$max],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $stripeRefund = null;
        if ($payment->provider === 'stripe') {
            if (! StripeService::enabled()) {
                throw ValidationException::withMessages(['amount' => 'Stripe is not configured, so this card payment cannot be refunded from here.']);
            }
            try {
                $stripeRefund = app(StripeService::class)->refund($payment, $amount);
            } catch (\Throwable $e) {
                report($e);
                throw ValidationException::withMessages(['amount' => 'Stripe rejected the refund: '.$e->getMessage()]);
            }
        }

        $total = round((float) $payment->refund_amount + $amount, 2);
        $full = $total >= (float) $payment->amount;
        $payment->forceFill([
            'refund_amount' => $total,
            'refund_reason' => $data['reason'] ?? $payment->refund_reason,
            'refunded_at' => now(),
            'status' => $full ? 'refunded' : 'partially_refunded',
            'transaction_reference' => $payment->transaction_reference ?: $stripeRefund,
        ])->save();

        $booking->update(['payment_status' => $full ? 'refunded' : 'partially_refunded']);
        if ($full) {
            $booking->invoice()?->update(['status' => 'refunded']);
        }
        \App\Models\AuditLog::record('payment.refund', $payment, "Refunded \${$amount} on {$booking->reference}");

        return back()->with('success', 'Refund recorded.');
    }
}
