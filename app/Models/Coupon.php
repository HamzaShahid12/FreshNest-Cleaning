<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_subtotal', 'max_uses',
        'used_count', 'starts_on', 'expires_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'starts_on' => 'date',
            'expires_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /** Null when usable, otherwise a customer-facing reason. */
    public function problem(): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is not active.';
        }
        if ($this->starts_on && $this->starts_on->isFuture()) {
            return 'This coupon is not valid yet.';
        }
        if ($this->expires_on && $this->expires_on->endOfDay()->isPast()) {
            return 'This coupon has expired.';
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'This coupon has reached its usage limit.';
        }

        return null;
    }

    /** Plain array consumed by the pure PricingCalculator. */
    public function toRule(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'value' => (float) $this->value,
            'min_subtotal' => (float) $this->min_subtotal,
        ];
    }
}
