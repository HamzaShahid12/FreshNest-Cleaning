<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Support\BookingView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Revenue = payments actually received, net of refunds.
        $revenue = fn (?Carbon $from = null) => (float) Payment::whereIn('status', ['paid', 'partially_refunded', 'refunded'])
            ->when($from, fn ($q) => $q->where('paid_at', '>=', $from))
            ->selectRaw('COALESCE(SUM(amount - refund_amount), 0) as net')->value('net');

        // Last 12 months of revenue.
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $paid = Payment::whereIn('status', ['paid', 'partially_refunded', 'refunded'])
            ->where('paid_at', '>=', $months->first())->get(['amount', 'refund_amount', 'paid_at']);
        $chart = $months->map(fn (Carbon $m) => [
            'label' => $m->format('M'),
            'value' => round((float) $paid->filter(fn ($p) => $p->paid_at->isSameMonth($m))->sum(fn ($p) => $p->amount - $p->refund_amount), 2),
        ])->values();

        $count = fn (string $status) => Booking::where('status', $status)->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'revenue_total' => $revenue(),
                'revenue_month' => $revenue(now()->startOfMonth()),
                'bookings_total' => Booking::count(),
                'bookings_today' => Booking::whereDate('scheduled_at', today())->whereNotIn('status', ['cancelled', 'rejected'])->count(),
                'pending' => $count('pending'),
                'confirmed' => $count('confirmed'),
                'completed' => $count('completed'),
                'customers' => User::where('role', 'customer')->count(),
                'employees' => User::where('role', 'staff')->where('is_active', true)->count(),
            ],
            'chart' => $chart,
            'statusBreakdown' => collect(config('freshnest.statuses'))->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $count($key)])->values(),
            'recentBookings' => Booking::with(['service', 'employee'])->latest('id')->limit(6)->get()->map(fn ($b) => BookingView::summary($b) + ['customer' => $b->contact_name]),
            'recentCustomers' => User::where('role', 'customer')->latest('id')->limit(5)->get(['id', 'name', 'email', 'created_at'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'joined' => $u->created_at->format('M j, Y')]),
            'upcoming' => Booking::with(['service', 'employee'])->active()->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(6)->get()
                ->map(fn ($b) => BookingView::summary($b) + ['customer' => $b->contact_name]),
        ]);
    }
}
