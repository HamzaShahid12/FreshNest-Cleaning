<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Services\StripeService;
use Illuminate\Validation\Rule;

class BookingRequest extends QuoteRequest
{
    /** Payment methods currently enabled by the business. */
    public static function enabledMethods(): array
    {
        $methods = [];
        if (Setting::bool('allow_pay_later')) {
            $methods[] = 'pay_later';
        }
        if (Setting::bool('allow_cash')) {
            $methods[] = 'cash';
        }
        if (StripeService::enabled()) {
            $methods[] = 'stripe';
        }

        // Never leave customers without a way to book.
        return $methods ?: ['pay_later'];
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'street' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]{1,19}$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'instructions' => ['nullable', 'string', 'max:1000'],

            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],

            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email:rfc', 'max:190'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Guests create an account while booking.
            'password' => [Rule::requiredIf(! $this->user()), 'nullable', 'string', 'min:8', 'max:100'],

            'payment_method' => ['required', Rule::in(self::enabledMethods())],
            'idempotency_key' => ['required', 'uuid'],
            'accept_terms' => ['accepted'],
        ]);
    }

    public function messages(): array
    {
        return [
            'accept_terms.accepted' => 'Please accept the terms and cancellation policy.',
            'zip.regex' => 'Enter a valid postal code.',
        ];
    }
}
