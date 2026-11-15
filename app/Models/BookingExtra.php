<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtra extends Model
{
    protected $fillable = ['booking_id', 'service_extra_id', 'name', 'price', 'quantity'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
