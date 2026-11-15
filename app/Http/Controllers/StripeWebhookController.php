<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\BookingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Stripe posts payment results here. The signature is verified with the webhook secret,
 * and every event id is recorded so a redelivered event is processed only once.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, BookingService $bookings): Response
    {
        $secret = config('freshnest.stripe.webhook_secret');
        if (blank($secret)) {
            return response('Webhook not configured', 503);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        // Idempotency: the unique index on event_id rejects a second insert.
        try {
            $record = WebhookEvent::create(['provider' => 'stripe', 'event_id' => $event->id, 'type' => $event->type]);
        } catch (QueryException) {
            return response('Already processed', 200);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if (($session->payment_status ?? null) === 'paid') {
                $payment = Payment::where('stripe_session_id', $session->id)->first();

                if ($payment) {
                    // Only accept the payment if the amount Stripe collected matches what we charged.
                    $expected = (int) round(((float) $payment->amount) * 100);
                    if ((int) $session->amount_total === $expected) {
                        $bookings->recordPayment($payment, $session->id, is_string($session->payment_intent ?? null) ? $session->payment_intent : null);
                    } else {
                        report(new \RuntimeException("Stripe amount mismatch for payment {$payment->id}: expected {$expected}, got {$session->amount_total}"));
                    }
                }
            }
        }

        $record->update(['processed_at' => now()]);

        return response('OK', 200);
    }
}
