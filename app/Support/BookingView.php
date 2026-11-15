<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Turns a Booking into the plain array the Vue pages receive. */
class BookingView
{
    public static function summary(Booking $b): array
    {
        return [
            'id' => $b->id,
            'reference' => $b->reference,
            'status' => $b->status,
            'status_label' => $b->statusLabel(),
            'service' => $b->service?->name,
            'service_slug' => $b->service?->slug,
            'scheduled_at' => $b->scheduled_at->toIso8601String(),
            'date' => $b->scheduled_at->format('D, M j, Y'),
            'date_iso' => $b->scheduled_at->format('Y-m-d'),
            'time' => $b->scheduled_at->format('g:i A'),
            'ends_time' => $b->ends_at->format('g:i A'),
            'duration_minutes' => $b->duration_minutes,
            'total' => (float) $b->total,
            'requires_quote' => $b->requires_quote,
            'payment_method' => $b->payment_method,
            'payment_status' => $b->payment_status,
            'city' => $b->city,
            'employee' => $b->employee?->name,
            'employee_id' => $b->employee_id,
        ];
    }

    /**
     * Full detail. Customer contact data and the street address are only included when the
     * viewer is allowed to see them (customer owner, admins, or the assigned cleaner once assigned).
     */
    public static function detail(Booking $b, ?User $viewer = null): array
    {
        $b->loadMissing(['service', 'employee.employeeProfile', 'extras', 'histories.actor', 'payments', 'invoice', 'review']);

        $viewer ??= auth()->user();
        $isOwner = $viewer && $b->user_id === $viewer->id;
        $showCustomer = $viewer && ($isOwner || Gate::forUser($viewer)->allows('viewCustomerDetails', $b));

        $data = self::summary($b) + [
            'frequency' => config('freshnest.frequencies')[$b->frequency] ?? $b->frequency,
            'property_type' => config('freshnest.property_types')[$b->property_type] ?? $b->property_type,
            'bedrooms' => $b->bedrooms,
            'bathrooms' => $b->bathrooms,
            'sqft' => $b->sqft,
            'instructions' => $b->instructions,
            'service_includes' => $b->service?->included ?? [],
            'extras' => $b->extras->map(fn ($e) => ['name' => $e->name, 'price' => (float) $e->price, 'quantity' => $e->quantity])->all(),
            'pricing' => [
                'subtotal' => (float) $b->subtotal,
                'discount_total' => (float) $b->discount_total,
                'tax' => (float) $b->tax,
                'total' => (float) $b->total,
                'coupon_code' => $b->coupon_code,
            ],
            'work_notes' => $b->work_notes,
            'completed_at' => $b->completed_at?->format('M j, Y g:i A'),
            'cancel_reason' => $b->cancel_reason,
            'employee_detail' => $b->employee ? [
                'id' => $b->employee->id,
                'name' => $b->employee->name,
                'job_title' => $b->employee->employeeProfile?->job_title,
            ] : null,
            'history' => $b->histories->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status, 'note' => $h->note,
                'by' => $h->actor?->name, 'at' => $h->created_at->format('M j, Y g:i A'),
            ])->all(),
            'invoice' => $b->invoice ? ['number' => $b->invoice->number, 'status' => $b->invoice->status, 'total' => (float) $b->invoice->total] : null,
            'review' => $b->review ? ['rating' => $b->review->rating, 'comment' => $b->review->comment, 'is_approved' => $b->review->is_approved] : null,
            'can_modify' => $isOwner ? $b->customerCanModify() : false,
            'can_review' => $isOwner && $b->status === 'completed' && ! $b->review,
        ];

        $data['customer'] = $showCustomer ? [
            'name' => $b->contact_name,
            'email' => $b->contact_email,
            'phone' => $b->contact_phone,
            'street' => $b->street,
            'unit' => $b->unit,
            'city' => $b->city,
            'state' => $b->state,
            'zip' => $b->zip,
            'country' => $b->country,
            'latitude' => $b->latitude !== null ? (float) $b->latitude : null,
            'longitude' => $b->longitude !== null ? (float) $b->longitude : null,
            'notes' => $b->notes,
        ] : null;

        $data['payments'] = ($isOwner || ($viewer && $viewer->isAdminRole()))
            ? $b->payments->map(fn ($p) => [
                'id' => $p->id, 'method' => $p->method, 'status' => $p->status, 'amount' => (float) $p->amount,
                'reference' => $p->transaction_reference, 'paid_at' => $p->paid_at?->format('M j, Y'),
            ])->all()
            : [];

        return $data;
    }
}
