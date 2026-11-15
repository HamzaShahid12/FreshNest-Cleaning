<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceArea extends Model
{
    protected $fillable = ['name', 'slug', 'state', 'country', 'zip_codes', 'description', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function zipList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->zip_codes))));
    }

    private static function norm(?string $v): string
    {
        $v = strtoupper(preg_replace('/\s+/', '', trim((string) $v)));

        // US ZIP+4 -> 5 digits
        return preg_match('/^(\d{5})-\d{4}$/', $v, $m) ? $m[1] : $v;
    }

    private static function normName(?string $v): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));
    }

    /** Does this area cover the address? A listed postal code decides when one is given; otherwise the city name is compared. */
    public function covers(?string $zip, ?string $city): bool
    {
        $zips = array_map([self::class, 'norm'], $this->zipList());
        if ($zips && self::norm($zip) !== '') {
            return in_array(self::norm($zip), $zips, true);
        }

        return self::normName($city) !== '' && self::normName($city) === self::normName($this->name);
    }

    /**
     * True when the location is served. With no active areas at all, everything is accepted
     * (so a fresh install is not blocked). Postal code is optional: customers in countries
     * without ZIP codes are matched by city name.
     */
    public static function serves(?string $zip, ?string $city = null): bool
    {
        $areas = static::where('is_active', true)->get();

        return $areas->isEmpty() || $areas->contains(fn (self $a) => $a->covers($zip, $city));
    }
}
