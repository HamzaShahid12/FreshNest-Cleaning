<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the pricing inputs (used by the live quote, availability and booking requests). */
class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'property_type' => ['required', Rule::in(array_keys(config('freshnest.property_types')))],
            'bedrooms' => ['nullable', 'integer', 'min:1', 'max:12'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sqft' => ['nullable', 'integer', 'min:0', 'max:30000'],
            'frequency' => ['required', Rule::in(array_keys(config('freshnest.frequencies')))],
            'extras' => ['nullable', 'array', 'max:20'],
            'extras.*' => ['integer', Rule::exists('service_extras', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ];
    }
}
