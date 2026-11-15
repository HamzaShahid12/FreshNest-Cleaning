<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-editable logo and page banners.
 * Uploaded files live on the "public" disk under branding/ and their paths are stored in business_settings.
 * Every banner has a shipped default in public/images/banners, so the site always looks finished.
 */
class Branding
{
    public const LOGO = 'logo';

    /** page key => admin label */
    public const PAGES = [
        'home' => 'Home page hero',
        'about' => 'About page',
        'services' => 'Services page',
        'pricing' => 'Pricing page',
        'gallery' => 'Gallery page',
        'reviews' => 'Reviews page',
        'faq' => 'FAQ page',
        'contact' => 'Contact page',
        'areas' => 'Service areas page',
        'blog' => 'Blog page',
        'legal' => 'Privacy / Terms pages',
    ];

    /** Logo height in pixels where it appears (header bar is 64 px tall, so its maximum is 56). */
    public const SIZES = ['header' => ['default' => 36, 'min' => 20, 'max' => 56], 'footer' => ['default' => 36, 'min' => 20, 'max' => 120]];

    public static function logoHeight(string $place): int
    {
        $c = self::SIZES[$place];
        $v = Setting::get("brand_logo_height_{$place}");

        return is_numeric($v) ? max($c['min'], min($c['max'], (int) $v)) : $c['default'];
    }

    public static function setLogoHeight(string $place, int $px): void
    {
        Setting::put("brand_logo_height_{$place}", (string) $px);
    }

    public static function keys(): array
    {
        return array_merge([self::LOGO], array_keys(self::PAGES));
    }

    public static function settingKey(string $key): string
    {
        return $key === self::LOGO ? 'brand_logo' : "brand_banner_{$key}";
    }

    public static function path(string $key): ?string
    {
        $v = Setting::get(self::settingKey($key));

        return is_string($v) && $v !== '' ? $v : null;
    }

    /** Public URL (relative, so it works on any host name). */
    public static function url(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    public static function defaultBanner(string $page): string
    {
        return "/images/banners/{$page}.svg";
    }

    public static function logoUrl(): ?string
    {
        $p = self::path(self::LOGO);

        return $p ? self::url($p) : null;
    }

    public static function bannerUrl(string $page): string
    {
        $p = self::path($page);

        return $p ? self::url($p) : self::defaultBanner($page);
    }

    /** Shared with every Inertia page. */
    public static function payload(): array
    {
        $banners = [];
        foreach (array_keys(self::PAGES) as $page) {
            $banners[$page] = self::bannerUrl($page);
        }

        return ['logo' => self::logoUrl(), 'banners' => $banners, 'logo_height' => ['header' => self::logoHeight('header'), 'footer' => self::logoHeight('footer')]];
    }

    /** Store an uploaded image, replacing (and deleting) the previous one. */
    public static function replace(string $key, \Illuminate\Http\UploadedFile $file): void
    {
        $old = self::path($key);
        $path = $file->store('branding', 'public'); // random file name; never trust the client name
        Setting::put(self::settingKey($key), $path);
        self::deleteFile($old);
    }

    public static function reset(string $key): void
    {
        $old = self::path($key);
        Setting::put(self::settingKey($key), '');
        self::deleteFile($old);
    }

    private static function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'branding/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
