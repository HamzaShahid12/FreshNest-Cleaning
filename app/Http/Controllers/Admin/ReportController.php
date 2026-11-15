<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TYPES = [
        'revenue' => 'Revenue',
        'bookings' => 'Bookings',
        'customers' => 'Customers',
        'employees' => 'Employee performance',
        'services' => 'Service performance',
        'cancellations' => 'Cancelled bookings',
    ];

    public function index(Request $request): Response
    {
        [$type, $from, $to, $group] = $this->params($request);
        [$columns, $rows] = $this->build($type, $from, $to, $group);

        return Inertia::render('Admin/Reports', [
            'types' => collect(self::TYPES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'filters' => ['type' => $type, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'group' => $group],
            'columns' => $columns,
            'rows' => $rows->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$type, $from, $to, $group] = $this->params($request);
        [$columns, $rows] = $this->build($type, $from, $to, $group);
        $name = "freshnest-{$type}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_column($columns, 'label'));
            foreach ($rows as $row) {
                // Prefix formula-looking cells so spreadsheet apps do not execute them (CSV injection).
                fputcsv($out, collect($columns)->map(function ($c) use ($row) {
                    $v = $row[$c['key']] ?? '';

                    return is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
                })->all());
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    private function params(Request $request): array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys(self::TYPES))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'group' => ['nullable', 'in:day,week,month,year'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$data['type'] ?? 'revenue', $from, $to, $data['group'] ?? 'day'];
    }

    /** @return array{0: array, 1: Collection} */
    private function build(string $type, Carbon $from, Carbon $to, string $group): array
    {
        return match ($type) {
            'revenue' => $this->revenue($from, $to, $group),
            'bookings' => $this->bookingsReport($from, $to),
            'customers' => $this->customers($from, $to),
            'employees' => $this->employees($from, $to),
            'services' => $this->services($from, $to),
            'cancellations' => $this->cancellations($from, $to),
        };
    }

    private function revenue(Carbon $from, Carbon $to, string $group): array
    {
        $fmt = match ($group) { 'year' => 'Y', 'month' => 'M Y', 'week' => 'o-\WW', default => 'Y-m-d' };

        $rows = Payment::whereIn('status', ['paid', 'partially_refunded', 'refunded'])->whereBetween('paid_at', [$from, $to])->get()
            ->groupBy(fn ($p) => $p->paid_at->format($fmt))
            ->map(fn ($g, $period) => [
                'period' => $period, 'payments' => $g->count(),
                'gross' => round((float) $g->sum('amount'), 2),
                'refunds' => round((float) $g->sum('refund_amount'), 2),
                'net' => round((float) $g->sum(fn ($p) => $p->amount - $p->refund_amount), 2),
            ])->values();

        return [[
            ['key' => 'period', 'label' => 'Period'], ['key' => 'payments', 'label' => 'Payments'],
            ['key' => 'gross', 'label' => 'Gross ($)'], ['key' => 'refunds', 'label' => 'Refunds ($)'], ['key' => 'net', 'label' => 'Net ($)'],
        ], $rows];
    }

    private function bookingsReport(Carbon $from, Carbon $to): array
    {
        $rows = Booking::with(['service', 'employee'])->whereBetween('scheduled_at', [$from, $to])->orderBy('scheduled_at')->get()
            ->map(fn ($b) => [
                'reference' => $b->reference, 'date' => $b->scheduled_at->format('Y-m-d H:i'), 'customer' => $b->contact_name,
                'service' => $b->service?->name, 'employee' => $b->employee?->name ?? '', 'status' => $b->statusLabel(),
                'total' => (float) $b->total, 'payment' => $b->payment_status,
            ]);

        return [[
            ['key' => 'reference', 'label' => 'Reference'], ['key' => 'date', 'label' => 'Date'], ['key' => 'customer', 'label' => 'Customer'],
            ['key' => 'service', 'label' => 'Service'], ['key' => 'employee', 'label' => 'Cleaner'], ['key' => 'status', 'label' => 'Status'],
            ['key' => 'total', 'label' => 'Total ($)'], ['key' => 'payment', 'label' => 'Payment'],
        ], $rows];
    }

    private function customers(Carbon $from, Carbon $to): array
    {
        $rows = User::where('role', 'customer')->withCount(['bookings' => fn ($q) => $q->whereBetween('scheduled_at', [$from, $to])])
            ->withSum(['bookings as spent' => fn ($q) => $q->where('status', 'completed')->whereBetween('scheduled_at', [$from, $to])], 'total')
            ->orderByDesc('bookings_count')->limit(500)->get()
            ->map(fn ($u) => ['name' => $u->name, 'email' => $u->email, 'joined' => $u->created_at->format('Y-m-d'), 'bookings' => $u->bookings_count, 'spent' => round((float) $u->spent, 2)]);

        return [[
            ['key' => 'name', 'label' => 'Customer'], ['key' => 'email', 'label' => 'Email'], ['key' => 'joined', 'label' => 'Joined'],
            ['key' => 'bookings', 'label' => 'Bookings in range'], ['key' => 'spent', 'label' => 'Completed value ($)'],
        ], $rows];
    }

    private function employees(Carbon $from, Carbon $to): array
    {
        $rows = User::where('role', 'staff')->orderBy('name')->get()->map(function ($u) use ($from, $to) {
            $jobs = Booking::where('employee_id', $u->id)->whereBetween('scheduled_at', [$from, $to]);

            return [
                'name' => $u->name,
                'assigned' => (clone $jobs)->count(),
                'completed' => (clone $jobs)->where('status', 'completed')->count(),
                'cancelled' => (clone $jobs)->where('status', 'cancelled')->count(),
                'value' => round((float) (clone $jobs)->where('status', 'completed')->sum('total'), 2),
                'rating' => round((float) \App\Models\Review::where('is_approved', true)->whereHas('booking', fn ($q) => $q->where('employee_id', $u->id))->avg('rating'), 1),
            ];
        });

        return [[
            ['key' => 'name', 'label' => 'Cleaner'], ['key' => 'assigned', 'label' => 'Assigned'], ['key' => 'completed', 'label' => 'Completed'],
            ['key' => 'cancelled', 'label' => 'Cancelled'], ['key' => 'value', 'label' => 'Completed value ($)'], ['key' => 'rating', 'label' => 'Avg rating'],
        ], $rows];
    }

    private function services(Carbon $from, Carbon $to): array
    {
        $rows = Booking::with('service')->whereBetween('scheduled_at', [$from, $to])->get()->groupBy('service_id')
            ->map(fn ($g) => [
                'service' => $g->first()->service?->name, 'bookings' => $g->count(),
                'completed' => $g->where('status', 'completed')->count(),
                'cancelled' => $g->whereIn('status', ['cancelled', 'rejected'])->count(),
                'revenue' => round((float) $g->where('status', 'completed')->sum('total'), 2),
            ])->sortByDesc('bookings');

        return [[
            ['key' => 'service', 'label' => 'Service'], ['key' => 'bookings', 'label' => 'Bookings'], ['key' => 'completed', 'label' => 'Completed'],
            ['key' => 'cancelled', 'label' => 'Cancelled / rejected'], ['key' => 'revenue', 'label' => 'Completed value ($)'],
        ], $rows];
    }

    private function cancellations(Carbon $from, Carbon $to): array
    {
        $rows = Booking::with('service')->whereIn('status', ['cancelled', 'rejected'])->whereBetween('scheduled_at', [$from, $to])->orderByDesc('scheduled_at')->get()
            ->map(fn ($b) => [
                'reference' => $b->reference, 'date' => $b->scheduled_at->format('Y-m-d H:i'), 'customer' => $b->contact_name,
                'service' => $b->service?->name, 'status' => $b->statusLabel(), 'reason' => $b->cancel_reason ?? '', 'total' => (float) $b->total,
            ]);

        return [[
            ['key' => 'reference', 'label' => 'Reference'], ['key' => 'date', 'label' => 'Date'], ['key' => 'customer', 'label' => 'Customer'],
            ['key' => 'service', 'label' => 'Service'], ['key' => 'status', 'label' => 'Status'], ['key' => 'reason', 'label' => 'Reason'], ['key' => 'total', 'label' => 'Value ($)'],
        ], $rows];
    }
}
