<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Logo and page banner images, editable at any time by Admin / Super Admin. */
class BrandingController extends Controller
{
    public function index(): Response
    {
        $items = [[
            'key' => Branding::LOGO, 'label' => 'Website logo',
            'hint' => 'PNG, JPG or WebP, transparent background recommended. Shown in the header, footer and portals.',
            'url' => Branding::logoUrl(), 'custom' => Branding::path(Branding::LOGO) !== null, 'default' => null, 'kind' => 'logo',
        ]];

        foreach (Branding::PAGES as $page => $label) {
            $items[] = [
                'key' => $page, 'label' => $label,
                'hint' => 'Wide image, at least 1600 × 600 px. JPG, PNG or WebP, up to 4 MB.',
                'url' => Branding::bannerUrl($page), 'custom' => Branding::path($page) !== null,
                'default' => Branding::defaultBanner($page), 'kind' => 'banner',
            ];
        }

        return Inertia::render('Admin/Branding', ['items' => $items, 'sizes' => ['header' => Branding::logoHeight('header'), 'footer' => Branding::logoHeight('footer')], 'limits' => Branding::SIZES]);
    }

    public function size(Request $request): RedirectResponse
    {
        $h = Branding::SIZES['header'];
        $f = Branding::SIZES['footer'];
        $data = $request->validate([
            'header' => ['required', 'integer', "between:{$h['min']},{$h['max']}"],
            'footer' => ['required', 'integer', "between:{$f['min']},{$f['max']}"],
        ]);
        Branding::setLogoHeight('header', (int) $data['header']);
        Branding::setLogoHeight('footer', (int) $data['footer']);
        AuditLog::record('branding.updated', null, 'Logo size changed');

        return back()->with('success', 'Logo size saved.');
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::keys(), true), 404);

        $max = $key === Branding::LOGO ? 2048 : 4096;
        $request->validate([
            // No SVG: an SVG opened directly could run scripts on this site's origin.
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$max}"],
        ], [
            'image.max' => 'The image is too large (maximum '.($max / 1024).' MB).',
            'image.mimes' => 'Please upload a JPG, PNG or WebP image.',
            'image.image' => 'The file must be an image.',
        ]);

        Branding::replace($key, $request->file('image'));
        AuditLog::record('branding.updated', null, "Image '{$key}' changed");

        return back()->with('success', 'Image updated.');
    }

    public function destroy(string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::keys(), true), 404);

        Branding::reset($key);
        AuditLog::record('branding.reset', null, "Image '{$key}' reset to default");

        return back()->with('success', 'Reset to the default image.');
    }
}
