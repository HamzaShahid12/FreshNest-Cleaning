<?php

namespace App\Support;

use App\Models\Setting;

class Seo
{
    /** schema.org LocalBusiness JSON-LD, rendered in the page <head>. */
    public static function localBusiness(): array
    {
        try {
            $s = Setting::map();
        } catch (\Throwable) {
            $s = [];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'HouseCleaningService',
            'name' => $s['business_name'] ?? 'FreshNest Cleaning Services',
            'description' => $s['tagline'] ?? 'Residential and commercial cleaning services.',
            'url' => url('/'),
            'telephone' => $s['business_phone'] ?? null,
            'email' => $s['business_email'] ?? null,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['business_address'] ?? null, ],
            'openingHours' => $s['business_hours'] ?? null,
        ];
        if (is_numeric($s['business_lat'] ?? null) && is_numeric($s['business_lng'] ?? null)) {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $s['business_lat'], 'longitude' => (float) $s['business_lng']];
        }

        $sameAs = array_values(array_filter([
            $s['social_facebook'] ?? null, $s['social_instagram'] ?? null,
            $s['social_x'] ?? null, $s['social_youtube'] ?? null,
        ]));
        if ($sameAs) {
            $data['sameAs'] = $sameAs;
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
