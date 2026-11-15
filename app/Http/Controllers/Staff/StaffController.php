<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Support\BookingView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Employee portal: a cleaner only ever sees jobs assigned to them. */
class StaffController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function dashboard(Request $request): Response
    {
        $mine = Booking::with('service')->where('employee_id', $request->user()->id);

        return Inertia::render('Staff/Dashboard', [
            'today' => (clone $mine)->active()->whereDate('scheduled_at', today())->orderBy('scheduled_at')->get()
                ->map(fn ($b) => $this->card($b, $request)),
            'upcoming' => (clone $mine)->active()->where('scheduled_at', '>', today()->endOfDay())->orderBy('scheduled_at')->limit(15)->get()
                ->map(fn ($b) => $this->card($b, $request)),
            'stats' => [
                'today' => (clone $mine)->active()->whereDate('scheduled_at', today())->count(),
                'upcoming' => (clone $mine)->active()->where('scheduled_at', '>', today()->endOfDay())->count(),
                'completed' => (clone $mine)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function history(Request $request): Response
    {
        return Inertia::render('Staff/History', [
            'jobs' => Booking::with('service')->where('employee_id', $request->user()->id)->where('status', 'completed')
                ->orderByDesc('scheduled_at')->paginate(15)->through(fn ($b) => $this->card($b, $request)),
        ]);
    }

    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('work', $booking);

        return Inertia::render('Staff/Job', [
            'job' => BookingView::detail($booking, $request->user()),
            'nextStatuses' => $this->nextStatuses($booking),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('work', $booking);
        $data = $request->validate(['status' => ['required', 'in:in_progress,completed']]);

        $this->bookings->transition($booking, $data['status'], $request->user(), $data['status'] === 'completed' ? 'Job completed by cleaner' : 'Job started');

        return back()->with('success', $data['status'] === 'completed' ? 'Job marked as completed.' : 'Job started.');
    }

    public function addNotes(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('work', $booking);
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        $line = '['.now()->format('M j g:i A').' '.$request->user()->name.'] '.trim($data['note']);
        $booking->work_notes = trim(($booking->work_notes ? $booking->work_notes."\n" : '').$line);
        $booking->save();

        return back()->with('success', 'Note added.');
    }

    private function nextStatuses(Booking $booking): array
    {
        return match ($booking->status) {
            'assigned', 'confirmed' => ['in_progress'],
            'in_progress' => ['completed'],
            default => [],
        };
    }

    /** List card: the customer's street address is only revealed once the job is assigned to this cleaner. */
    private function card(Booking $b, Request $request): array
    {
        $s = BookingView::summary($b);
        $s['address'] = Gate::forUser($request->user())->allows('viewCustomerDetails', $b)
            ? trim($b->street.' '.$b->unit).', '.$b->city
            : null;

        return $s;
    }
}
