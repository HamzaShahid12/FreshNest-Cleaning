<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'business_settings';

    protected $fillable = ['key', 'value'];

    /** Flat key => default map taken from config/freshnest.php. */
    public static function defaults(): array
    {
        $out = [];
        foreach (config('freshnest.settings') as $group) {
            foreach ($group['fields'] as $key => $field) {
                $out[$key] = (string) $field['default'];
            }
        }

        return $out;
    }

    public static function buildMap(): array
    {
        try {
            $stored = static::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            $stored = []; // database not migrated yet
        }

        return array_merge(static::defaults(), array_filter($stored, fn ($v) => $v !== null));
    }

    public static function map(): array
    {
        return app('fn.settings');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::map()[$key] ?? $default;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $v = static::get($key);

        return is_numeric($v) ? (float) $v : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = static::get($key);

        return is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key): bool
    {
        return in_array(strtolower((string) static::get($key, '0')), ['1', 'true', 'yes', 'on'], true);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        app()->forgetInstance('fn.settings');
    }

    /** Pricing rule values used by the price calculator. */
    public static function rates(): array
    {
        $out = [];
        foreach (array_keys(config('freshnest.settings.pricing.fields')) as $key) {
            $out[$key] = static::float($key);
        }

        return $out;
    }

    /** Settings that are safe to expose to every visitor. */
    public static function publicBusiness(): array
    {
        $s = static::map();

        return [
            'name' => $s['business_name'],
            'tagline' => $s['tagline'],
            'email' => $s['business_email'],
            'phone' => $s['business_phone'],
            'address' => $s['business_address'],
            'hours' => $s['business_hours'],
            'maps_embed_url' => $s['maps_embed_url'],
            'lat' => is_numeric($s['business_lat'] ?? null) ? (float) $s['business_lat'] : null,
            'lng' => is_numeric($s['business_lng'] ?? null) ? (float) $s['business_lng'] : null,
            'announcement' => $s['announcement'],
            'social' => [
                'facebook' => $s['social_facebook'],
                'instagram' => $s['social_instagram'],
                'x' => $s['social_x'],
                'youtube' => $s['social_youtube'],
            ],
            'cancellation_hours' => (int) $s['cancellation_hours'],
        ];
    }
}
