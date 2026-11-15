<?php

namespace App\Services;

/**
 * Pure price/duration calculator (no database, no framework) so it can be unit tested in isolation.
 *
 * All money math is done in integer cents to avoid floating point drift.
 */
class PricingCalculator
{
    /**
     * @param  array  $service  ['name','base_price','pricing_method','duration_minutes']
     * @param  array  $input    ['property_type','bedrooms','bathrooms','sqft','frequency']
     * @param  array  $extras   [['id','name','price','duration_minutes','quantity'?], ...]
     * @param  array  $rates    pricing settings (see config/freshnest.php > settings.pricing)
     * @param  array|null  $coupon  ['code','type','value','min_subtotal'] (already checked for active/expiry)
     */
    public static function calculate(array $service, array $input, array $extras, array $rates, ?array $coupon = null): array
    {
        $method = $service['pricing_method'] ?? 'size_based';
        $baseDuration = (int) ($service['duration_minutes'] ?? 120);
        $frequency = $input['frequency'] ?? 'one_time';

        // Custom quotation: nothing to price online.
        if ($method === 'quote') {
            return [
                'requires_quote' => true,
                'lines' => [],
                'subtotal' => 0.0,
                'frequency_discount' => 0.0,
                'frequency_discount_percent' => 0.0,
                'coupon_discount' => 0.0,
                'coupon_error' => null,
                'discount_total' => 0.0,
                'tax_rate' => (float) ($rates['tax_rate'] ?? 0),
                'tax' => 0.0,
                'total' => 0.0,
                'duration_minutes' => self::roundUp($baseDuration),
            ];
        }

        $lines = [];
        $minutes = $baseDuration;
        $cents = self::toCents($service['base_price'] ?? 0);
        $lines[] = ['label' => ($service['name'] ?? 'Service').' (base price)', 'amount' => self::fromCents($cents), 'type' => 'base'];

        $cleaningCents = $cents;

        if ($method === 'size_based') {
            $bedrooms = max(1, (int) ($input['bedrooms'] ?? 1));
            $bathrooms = max(1, (int) ($input['bathrooms'] ?? 1));
            $sqft = (int) ($input['sqft'] ?? 0);

            $extraBeds = $bedrooms - 1;
            if ($extraBeds > 0) {
                $c = self::toCents(($rates['bedroom_rate'] ?? 0) * $extraBeds);
                $lines[] = ['label' => "Additional bedrooms ({$extraBeds})", 'amount' => self::fromCents($c), 'type' => 'size'];
                $cleaningCents += $c;
                $minutes += $extraBeds * (int) ($rates['bedroom_minutes'] ?? 0);
            }

            $extraBaths = $bathrooms - 1;
            if ($extraBaths > 0) {
                $c = self::toCents(($rates['bathroom_rate'] ?? 0) * $extraBaths);
                $lines[] = ['label' => "Additional bathrooms ({$extraBaths})", 'amount' => self::fromCents($c), 'type' => 'size'];
                $cleaningCents += $c;
                $minutes += $extraBaths * (int) ($rates['bathroom_minutes'] ?? 0);
            }

            $included = (int) ($rates['sqft_included'] ?? 0);
            $blockSize = max(1, (int) ($rates['sqft_block_size'] ?? 500));
            if ($sqft > $included) {
                $blocks = (int) ceil(($sqft - $included) / $blockSize);
                $c = self::toCents(($rates['sqft_block_rate'] ?? 0) * $blocks);
                $lines[] = ['label' => "Larger property ({$blocks} x {$blockSize} sq ft)", 'amount' => self::fromCents($c), 'type' => 'size'];
                $cleaningCents += $c;
                $minutes += $blocks * (int) ($rates['sqft_block_minutes'] ?? 0);
            }

            $multiplier = (float) ($rates[($input['property_type'] ?? 'house').'_multiplier'] ?? 1.0);
            if ($multiplier > 1.0) {
                $adj = (int) round($cleaningCents * ($multiplier - 1));
                $lines[] = ['label' => 'Property type adjustment', 'amount' => self::fromCents($adj), 'type' => 'size'];
                $cleaningCents += $adj;
            }
        }

        $subtotalCents = $cleaningCents;

        foreach ($extras as $extra) {
            $qty = max(1, (int) ($extra['quantity'] ?? 1));
            $c = self::toCents($extra['price']) * $qty;
            $lines[] = ['label' => $extra['name'].($qty > 1 ? " x{$qty}" : ''), 'amount' => self::fromCents($c), 'type' => 'extra'];
            $subtotalCents += $c;
            $minutes += (int) ($extra['duration_minutes'] ?? 0) * $qty;
        }

        // Recurring discount
        $freqPercent = match ($frequency) {
            'weekly' => (float) ($rates['discount_weekly'] ?? 0),
            'biweekly' => (float) ($rates['discount_biweekly'] ?? 0),
            'monthly' => (float) ($rates['discount_monthly'] ?? 0),
            default => 0.0,
        };
        $freqCents = (int) round($subtotalCents * $freqPercent / 100);

        // Coupon (applied after the recurring discount)
        $couponCents = 0;
        $couponError = null;
        if ($coupon) {
            $minCents = self::toCents($coupon['min_subtotal'] ?? 0);
            if ($subtotalCents < $minCents) {
                $couponError = 'Minimum order of $'.number_format($minCents / 100, 2).' required for this coupon.';
            } else {
                $remaining = $subtotalCents - $freqCents;
                $couponCents = ($coupon['type'] ?? 'percent') === 'percent'
                    ? (int) round($remaining * ((float) $coupon['value']) / 100)
                    : self::toCents($coupon['value']);
                $couponCents = max(0, min($couponCents, $remaining));
            }
        }

        $discountCents = $freqCents + $couponCents;
        $taxableCents = max(0, $subtotalCents - $discountCents);
        $taxRate = (float) ($rates['tax_rate'] ?? 0);
        $taxCents = (int) round($taxableCents * $taxRate / 100);
        $totalCents = $taxableCents + $taxCents;

        return [
            'requires_quote' => false,
            'lines' => $lines,
            'subtotal' => self::fromCents($subtotalCents),
            'frequency_discount' => self::fromCents($freqCents),
            'frequency_discount_percent' => $freqPercent,
            'coupon_discount' => self::fromCents($couponCents),
            'coupon_error' => $couponError,
            'discount_total' => self::fromCents($discountCents),
            'tax_rate' => $taxRate,
            'tax' => self::fromCents($taxCents),
            'total' => self::fromCents($totalCents),
            'duration_minutes' => self::roundUp($minutes),
        ];
    }

    public static function toCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /** Appointments are planned in 15 minute blocks. */
    public static function roundUp(int $minutes): int
    {
        return (int) (ceil(max(15, $minutes) / 15) * 15);
    }
}
