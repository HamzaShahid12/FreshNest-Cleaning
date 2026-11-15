<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\Setting;

/**
 * Loads the service, extras, coupon and admin-configured rates from the database,
 * then delegates the arithmetic to PricingCalculator. This is the ONLY source of
 * prices: the browser's numbers are for display and are never trusted.
 */
class PricingService
{
    public function quote(array $input): array
    {
        $service = Service::active()->findOrFail($input['service_id']);

        $extraIds = array_values(array_unique(array_map('intval', $input['extras'] ?? [])));
        $extras = $extraIds
            ? ServiceExtra::where('is_active', true)->whereIn('id', $extraIds)->get()
            : collect();

        $couponRule = null;
        $couponProblem = null;
        $coupon = Coupon::findByCode($input['coupon_code'] ?? null);
        if (! empty($input['coupon_code'])) {
            if (! $coupon) {
                $couponProblem = 'That coupon code is not valid.';
            } elseif ($problem = $coupon->problem()) {
                $couponProblem = $problem;
            } else {
                $couponRule = $coupon->toRule();
            }
        }

        $result = PricingCalculator::calculate(
            [
                'name' => $service->name,
                'base_price' => (float) $service->base_price,
                'pricing_method' => $service->pricing_method,
                'duration_minutes' => $service->duration_minutes,
            ],
            $input,
            $extras->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'price' => (float) $e->price,
                'duration_minutes' => $e->duration_minutes,
                'quantity' => 1,
            ])->all(),
            Setting::rates(),
            $couponRule,
        );

        $result['coupon_error'] = $couponProblem ?? $result['coupon_error'];
        $result['coupon_id'] = ($couponRule && ! $result['coupon_error']) ? $coupon->id : null;
        $result['coupon_code'] = ($couponRule && ! $result['coupon_error']) ? $coupon->code : null;
        $result['extras'] = $extras->all();
        $result['service'] = $service;

        return $result;
    }

    /** JSON-safe version for the booking wizard. */
    public function quoteForClient(array $input): array
    {
        $q = $this->quote($input);
        unset($q['extras'], $q['service']);

        return $q;
    }
}
