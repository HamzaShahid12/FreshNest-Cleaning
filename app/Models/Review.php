<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['booking_id', 'user_id', 'service_id', 'rating', 'comment', 'is_approved'];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean', 'rating' => 'integer'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    /** "Jane D." - never expose a full customer name publicly. */
    public function publicName(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user?->name)) ?: [];
        $first = $parts[0] ?? 'Customer';
        $last = isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '';

        return $first.$last;
    }
}
