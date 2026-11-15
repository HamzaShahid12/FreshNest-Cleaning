<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /** Set only by server-side code (never from raw request input). */
    protected $fillable = [
        'reference', 'idempotency_key', 'user_id', 'service_id', 'employee_id', 'coupon_id', 'coupon_code',
        'property_type', 'bedrooms', 'bathrooms', 'sqft', 'frequency',
        'scheduled_at', 'ends_at', 'duration_minutes',
        'street', 'unit', 'city', 'state', 'country', 'country_code', 'latitude', 'longitude', 'zip', 'instructions',
        'contact_name', 'contact_email', 'contact_phone', 'notes',
        'subtotal', 'discount_total', 'tax', 'total', 'requires_quote',
        'status', 'payment_method', 'payment_status',
        'completed_at', 'cancelled_at', 'cancel_reason', 'work_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'requires_quote' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** Bookings that currently occupy a time slot. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', config('freshnest.active_statuses'));
    }

    /** Bookings whose time window overlaps [start, end). */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('scheduled_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function statusLabel(): string
    {
        return config('freshnest.statuses')[$this->status] ?? $this->status;
    }

    /** Can the customer cancel/reschedule on their own (inside the free-change window)? */
    public function customerCanModify(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed', 'assigned'], true)) {
            return false;
        }
        $hours = Setting::int('cancellation_hours', 24);

        return $this->scheduled_at->gte(now()->addHours($hours));
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'FN-'.now()->format('ymd').'-'.strtoupper(\Illuminate\Support\Str::random(5));
        } while (static::withTrashed()->where('reference', $ref)->exists());

        return $ref;
    }
}
