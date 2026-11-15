<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    /** Builds a correctly signed Stripe webhook request body + header. */
    private function signed(array $event): array
    {
        $body = json_encode($event);
        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$body}", self::SECRET);

        return [$body, "t={$t},v1={$sig}"];
    }

    private function sessionEvent(string $eventId, Payment $payment, int $amountCents, string $status = 'paid'): array
    {
        return [
            'id' => $eventId, 'object' => 'event', 'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => $payment->stripe_session_id, 'object' => 'checkout.session', 'payment_status' => $status, 'amount_total' => $amountCents, 'payment_intent' => 'pi_123']],
        ];
    }

    private function stripePayment(): Payment
    {
        $booking = Booking::factory()->create(['payment_method' => 'stripe', 'total' => 80]);

        return Payment::create([
            'booking_id' => $booking->id, 'user_id' => $booking->user_id, 'provider' => 'stripe', 'method' => 'stripe',
            'amount' => 80, 'status' => 'pending', 'stripe_session_id' => 'cs_test_1',
        ]);
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $payment = $this->stripePayment();
        [$body] = $this->signed($this->sessionEvent('evt_1', $payment, 8000));

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_valid_webhook_marks_payment_paid_exactly_once(): void
    {
        $payment = $this->stripePayment();
        [$body, $header] = $this->signed($this->sessionEvent('evt_1', $payment, 8000));
        $server = ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/stripe/webhook', [], [], [], $server, $body)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $payment->booking->fresh()->payment_status);
        $this->assertDatabaseCount('webhook_events', 1);

        // Stripe redelivers the same event: it must be acknowledged but not processed again.
        $paidAt = $payment->fresh()->paid_at;
        $this->call('POST', '/stripe/webhook', [], [], [], $server, $body)->assertOk();
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertEquals($paidAt, $payment->fresh()->paid_at);
    }

    public function test_webhook_with_mismatched_amount_does_not_mark_paid(): void
    {
        $payment = $this->stripePayment();
        [$body, $header] = $this->signed($this->sessionEvent('evt_2', $payment, 100));

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_unpaid_session_does_not_mark_paid(): void
    {
        $payment = $this->stripePayment();
        [$body, $header] = $this->signed($this->sessionEvent('evt_3', $payment, 8000, 'unpaid'));

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_admin_records_offline_payment_and_partial_refund(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create(['total' => 100]);
        Payment::create(['booking_id' => $booking->id, 'user_id' => $booking->user_id, 'provider' => 'offline', 'method' => 'cash', 'amount' => 100, 'status' => 'pending']);
        Invoice::create(['booking_id' => $booking->id, 'number' => 'INV-T-1', 'issued_on' => today(), 'total' => 100, 'status' => 'unpaid']);

        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/payment", ['reference' => 'CASH-1'])->assertSessionHas('success');
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('paid', $booking->invoice->fresh()->status);

        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/refund", ['amount' => 30, 'reason' => 'Goodwill'])->assertSessionHas('success');
        $this->assertSame('partially_refunded', $booking->fresh()->payment_status);

        // Cannot refund more than what was paid.
        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/refund", ['amount' => 100])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/refund", ['amount' => 70])->assertSessionHas('success');
        $this->assertSame('refunded', $booking->fresh()->payment_status);
    }
}
