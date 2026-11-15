<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceExtra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create(['slug' => 'standard', 'base_price' => 80, 'duration_minutes' => 120]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'service_id' => $this->service->id,
            'property_type' => 'apartment', 'bedrooms' => 1, 'bathrooms' => 1, 'sqft' => 900, 'frequency' => 'one_time',
            'extras' => [],
            'street' => '12 Oak Street', 'city' => 'Austin', 'state' => 'TX', 'zip' => '78701',
            'date' => now()->addDays(3)->format('Y-m-d'), 'time' => '10:00',
            'contact_name' => 'Jane Doe', 'contact_email' => 'jane@example.test', 'contact_phone' => '(555) 010-2030',
            'payment_method' => 'pay_later', 'idempotency_key' => (string) Str::uuid(), 'accept_terms' => true,
        ], $over);
    }

    public function test_quote_is_calculated_on_the_server(): void
    {
        $fridge = ServiceExtra::factory()->create(['name' => 'Fridge', 'price' => 20]);

        $json = $this->postJson('/api/booking/quote', [
            'service_id' => $this->service->id, 'property_type' => 'apartment', 'bedrooms' => 3, 'bathrooms' => 2, 'sqft' => 1800,
            'frequency' => 'weekly', 'extras' => [$fridge->id],
        ])->assertOk()->json();

        $this->assertEquals(174, $json['subtotal']);
        $this->assertEquals(26.1, $json['frequency_discount']);
        $this->assertEquals(147.9, $json['total']);
    }

    public function test_quote_rejects_invalid_input(): void
    {
        $this->postJson('/api/booking/quote', ['service_id' => 9999, 'property_type' => 'castle', 'frequency' => 'daily'])
            ->assertStatus(422)->assertJsonValidationErrors(['service_id', 'property_type', 'frequency']);
    }

    public function test_availability_is_empty_without_staff_and_opens_with_staff(): void
    {
        $query = http_build_query(['service_id' => $this->service->id, 'property_type' => 'apartment', 'frequency' => 'one_time', 'date' => now()->addDays(3)->format('Y-m-d')]);

        $slots = $this->getJson('/api/booking/availability?'.$query)->assertOk()->json('slots');
        $this->assertTrue(collect($slots)->every(fn ($s) => $s['available'] === false));

        User::factory()->staff()->create();
        $slots = $this->getJson('/api/booking/availability?'.$query)->assertOk()->json('slots');
        $this->assertTrue(collect($slots)->contains(fn ($s) => $s['available'] === true));
    }

    public function test_guest_booking_creates_account_booking_invoice_and_payment(): void
    {
        User::factory()->staff()->create();

        $this->postJson('/api/booking', $this->payload(['password' => 'Secret1234']))->assertCreated()->assertJsonStructure(['reference', 'redirect']);

        $booking = Booking::firstOrFail();
        $this->assertSame('pending', $booking->status);
        $this->assertEquals(80.0, (float) $booking->total);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.test', 'role' => 'customer']);
        $this->assertDatabaseHas('invoices', ['booking_id' => $booking->id]);
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'status' => 'pending']);
        $this->assertDatabaseHas('booking_status_histories', ['booking_id' => $booking->id, 'to_status' => 'pending']);
        $this->assertAuthenticated();
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->postJson('/api/booking', $this->payload(['total' => 1, 'subtotal' => 1, 'base_price' => 1]))->assertCreated();

        $this->assertEquals(80.0, (float) Booking::firstOrFail()->total);
    }

    public function test_duplicate_submission_returns_the_same_booking(): void
    {
        User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $payload = $this->payload();

        $a = $this->actingAs($customer)->postJson('/api/booking', $payload)->assertCreated()->json('reference');
        $b = $this->actingAs($customer)->postJson('/api/booking', $payload)->assertCreated()->json('reference');

        $this->assertSame($a, $b);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_double_booking_is_prevented_when_capacity_is_used(): void
    {
        User::factory()->staff()->create(); // exactly one cleaner
        $c1 = User::factory()->customer()->create();
        $c2 = User::factory()->customer()->create();

        $this->actingAs($c1)->postJson('/api/booking', $this->payload())->assertCreated();
        $this->actingAs($c2)->postJson('/api/booking', $this->payload(['idempotency_key' => (string) Str::uuid()]))
            ->assertStatus(422)->assertJsonValidationErrors('time');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_overlapping_times_use_the_service_duration(): void
    {
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();
        $this->actingAs($c)->postJson('/api/booking', $this->payload(['time' => '10:00']))->assertCreated(); // 10:00-12:00

        // 11:00 overlaps the 2-hour job; 12:00 does not.
        $this->actingAs($c)->postJson('/api/booking', $this->payload(['time' => '11:00', 'idempotency_key' => (string) Str::uuid()]))->assertStatus(422);
        $this->actingAs($c)->postJson('/api/booking', $this->payload(['time' => '12:00', 'idempotency_key' => (string) Str::uuid()]))->assertCreated();
    }

    public function test_cannot_book_inside_minimum_notice_or_without_terms(): void
    {
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['date' => now()->format('Y-m-d'), 'time' => now()->addHour()->format('H:00')]))->assertStatus(422);
        $this->actingAs($c)->postJson('/api/booking', $this->payload(['accept_terms' => false]))->assertStatus(422)->assertJsonValidationErrors('accept_terms');
    }

    public function test_zip_outside_service_areas_is_refused(): void
    {
        ServiceArea::create(['name' => 'Austin', 'slug' => 'austin', 'state' => 'TX', 'zip_codes' => '78701', 'is_active' => true]);
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['zip' => '90210']))->assertStatus(422)->assertJsonValidationErrors('zip');
    }

    public function test_booking_can_be_made_from_any_country_with_map_coordinates(): void
    {
        ServiceArea::create(['name' => 'Austin', 'slug' => 'austin', 'state' => 'TX', 'zip_codes' => '78701', 'is_active' => true]);
        // A city-only area (no postal codes) for a country that does not use ZIP codes.
        ServiceArea::create(['name' => 'Lahore', 'slug' => 'lahore', 'country' => 'Pakistan', 'is_active' => true]);
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload([
            'street' => '5 Mall Road', 'city' => 'Lahore', 'state' => 'Punjab', 'zip' => null,
            'country' => 'Pakistan', 'country_code' => 'PK', 'latitude' => 31.5204, 'longitude' => 74.3587,
        ]))->assertCreated();

        $this->assertDatabaseHas('bookings', ['city' => 'Lahore', 'country' => 'Pakistan', 'country_code' => 'PK', 'zip' => null]);
        $this->assertEquals(31.5204, (float) \App\Models\Booking::first()->latitude);
    }

    public function test_city_outside_the_listed_areas_is_refused_unless_the_restriction_is_switched_off(): void
    {
        ServiceArea::create(['name' => 'Lahore', 'slug' => 'lahore', 'is_active' => true]);
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();
        $karachi = ['city' => 'Karachi', 'state' => null, 'zip' => null, 'country' => 'Pakistan', 'country_code' => 'PK'];

        $this->actingAs($c)->postJson('/api/booking', $this->payload($karachi))->assertStatus(422)->assertJsonValidationErrors('city');

        \App\Models\Setting::put('restrict_service_area', '0');
        $this->actingAs($c)->postJson('/api/booking', $this->payload($karachi))->assertCreated();
    }

    public function test_postal_code_and_state_are_optional_and_coordinates_are_validated(): void
    {
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['latitude' => 120, 'country' => 'Ireland']))
            ->assertStatus(422)->assertJsonValidationErrors('latitude');
        $this->actingAs($c)->postJson('/api/booking', $this->payload(['state' => null, 'zip' => null, 'country' => 'United Arab Emirates', 'country_code' => 'AE']))
            ->assertCreated();
    }

    public function test_coupon_is_validated_and_usage_counted(): void
    {
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();
        $coupon = Coupon::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['coupon_code' => 'welcome10']))->assertCreated();

        $this->assertEquals(72.0, (float) Booking::firstOrFail()->total);
        $this->assertSame(1, $coupon->fresh()->used_count);

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['coupon_code' => 'NOPE', 'time' => '14:00', 'idempotency_key' => (string) Str::uuid()]))
            ->assertStatus(422)->assertJsonValidationErrors('coupon_code');
    }

    public function test_office_cleaning_is_a_custom_quote(): void
    {
        $office = Service::factory()->quote()->create();
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['service_id' => $office->id, 'property_type' => 'office']))->assertCreated();

        $b = Booking::firstOrFail();
        $this->assertTrue($b->requires_quote);
        $this->assertEquals(0.0, (float) $b->total);
    }

    public function test_stripe_is_refused_when_not_configured(): void
    {
        User::factory()->staff()->create();
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->postJson('/api/booking', $this->payload(['payment_method' => 'stripe']))
            ->assertStatus(422)->assertJsonValidationErrors('payment_method');
    }

    public function test_existing_email_must_sign_in_instead_of_creating_a_second_account(): void
    {
        User::factory()->staff()->create();
        User::factory()->customer()->create(['email' => 'jane@example.test']);

        $this->postJson('/api/booking', $this->payload(['password' => 'Secret1234']))->assertStatus(422)->assertJsonValidationErrors('contact_email');
    }
}
