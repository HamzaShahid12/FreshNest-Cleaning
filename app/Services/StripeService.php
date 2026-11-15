<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Stripe\StripeClient;

/**
 * Thin wrapper around Stripe Checkout. Card data never touches this server: customers pay on
 * Stripe's hosted page. When STRIPE_SECRET is empty the feature is simply disabled; no payment
 * is ever simulated as successful.
 */
class StripeService
{
    public static function enabled(): bool
    {
        return filled(config('freshnest.stripe.secret'));
    }

    public function client(): StripeClient
    {
        return new StripeClient(config('freshnest.stripe.secret'));
    }

    /** Returns the Stripe-hosted checkout URL for the booking. */
    public function checkoutUrl(Booking $booking): string
    {
        $cents = PricingCalculator::toCents($booking->total);

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => $booking->reference,
            'customer_email' => $booking->contact_email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $cents,
                    'product_data' => ['name' => $booking->service->name.' - '.$booking->reference],
                ],
            ]],
            'metadata' => ['booking_id' => (string) $booking->id, 'reference' => $booking->reference],
            'success_url' => route('booking.confirmation', $booking).'?paid=1',
            'cancel_url' => route('booking.confirmation', $booking).'?cancelled=1',
        ], [
            // Same booking + same amount always maps to the same session (idempotent retries).
            'idempotency_key' => "booking-{$booking->id}-{$cents}",
        ]);

        Payment::where('booking_id', $booking->id)->where('provider', 'stripe')->where('status', 'pending')
            ->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    /** Refund through Stripe when the payment came from Stripe. */
    public function refund(Payment $payment, float $amount): ?string
    {
        if (! $payment->payment_intent_id) {
            return null;
        }
        $refund = $this->client()->refunds->create([
            'payment_intent' => $payment->payment_intent_id,
            'amount' => PricingCalculator::toCents($amount),
        ]);

        return $refund->id;
    }
}
