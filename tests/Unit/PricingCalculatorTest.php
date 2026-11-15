<?php

namespace Tests\Unit;

use App\Services\PricingCalculator as P;
use PHPUnit\Framework\TestCase;

class PricingCalculatorTest extends TestCase
{
    private array $rates = [
        'bedroom_rate' => 15, 'bathroom_rate' => 20, 'sqft_included' => 1000, 'sqft_block_size' => 500, 'sqft_block_rate' => 12,
        'apartment_multiplier' => 1, 'house_multiplier' => 1.1, 'office_multiplier' => 1.2,
        'discount_weekly' => 15, 'discount_biweekly' => 10, 'discount_monthly' => 5, 'tax_rate' => 0,
        'bedroom_minutes' => 30, 'bathroom_minutes' => 20, 'sqft_block_minutes' => 15,
    ];

    private array $standard = ['name' => 'Standard', 'base_price' => 80, 'pricing_method' => 'size_based', 'duration_minutes' => 120];

    private array $extras = [
        ['id' => 1, 'name' => 'Fridge', 'price' => 20, 'duration_minutes' => 30],
        ['id' => 2, 'name' => 'Oven', 'price' => 25, 'duration_minutes' => 30],
    ];

    public function test_base_price_for_small_apartment(): void
    {
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'bedrooms' => 1, 'bathrooms' => 1, 'sqft' => 900, 'frequency' => 'one_time'], [], $this->rates);

        $this->assertSame(80.0, $r['total']);
        $this->assertSame(120, $r['duration_minutes']);
    }

    public function test_size_extras_and_addons_are_itemised(): void
    {
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'bedrooms' => 3, 'bathrooms' => 2, 'sqft' => 1800, 'frequency' => 'one_time'], $this->extras, $this->rates);

        // 80 + 2 bedrooms (30) + 1 bathroom (20) + 2 size blocks (24) = 154; extras 45 => 199
        $this->assertSame(199.0, $r['subtotal']);
        $this->assertSame(199.0, $r['total']);
        $this->assertSame(300, $r['duration_minutes']);
        $this->assertNotEmpty($r['lines']);
    }

    public function test_weekly_discount_and_house_multiplier(): void
    {
        $r = P::calculate($this->standard, ['property_type' => 'house', 'bedrooms' => 1, 'bathrooms' => 1, 'sqft' => 0, 'frequency' => 'weekly'], [], $this->rates);

        $this->assertSame(88.0, $r['subtotal']);
        $this->assertSame(13.2, $r['frequency_discount']);
        $this->assertSame(74.8, $r['total']);
    }

    public function test_percent_coupon(): void
    {
        $coupon = ['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0];
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'frequency' => 'one_time'], [], $this->rates, $coupon);

        $this->assertSame(8.0, $r['coupon_discount']);
        $this->assertSame(72.0, $r['total']);
    }

    public function test_coupon_below_minimum_is_rejected(): void
    {
        $coupon = ['code' => 'BIG', 'type' => 'fixed', 'value' => 20, 'min_subtotal' => 200];
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'frequency' => 'one_time'], [], $this->rates, $coupon);

        $this->assertNotNull($r['coupon_error']);
        $this->assertSame(80.0, $r['total']);
    }

    public function test_fixed_coupon_never_makes_total_negative(): void
    {
        $coupon = ['code' => 'HUGE', 'type' => 'fixed', 'value' => 500, 'min_subtotal' => 0];
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'frequency' => 'one_time'], [], $this->rates, $coupon);

        $this->assertSame(0.0, $r['total']);
    }

    public function test_tax_is_applied_after_discounts(): void
    {
        $r = P::calculate($this->standard, ['property_type' => 'apartment', 'frequency' => 'one_time'], [], array_merge($this->rates, ['tax_rate' => 8.25]));

        $this->assertSame(6.6, $r['tax']);
        $this->assertSame(86.6, $r['total']);
    }

    public function test_quote_services_require_a_custom_quotation(): void
    {
        $office = ['name' => 'Office', 'base_price' => 0, 'pricing_method' => 'quote', 'duration_minutes' => 180];
        $r = P::calculate($office, ['property_type' => 'office'], [], $this->rates);

        $this->assertTrue($r['requires_quote']);
        $this->assertSame(0.0, $r['total']);
    }

    public function test_flat_services_ignore_property_size(): void
    {
        $flat = ['name' => 'Carpet', 'base_price' => 60, 'pricing_method' => 'flat', 'duration_minutes' => 90];
        $r = P::calculate($flat, ['property_type' => 'house', 'bedrooms' => 5, 'bathrooms' => 4, 'sqft' => 5000], [], $this->rates);

        $this->assertSame(60.0, $r['total']);
    }

    public function test_cents_conversion_avoids_float_drift(): void
    {
        $this->assertSame(1999, P::toCents(19.99));
        $this->assertSame(0.3, P::fromCents(P::toCents(0.1) + P::toCents(0.2)));
    }
}
