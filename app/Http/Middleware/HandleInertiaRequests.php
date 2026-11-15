<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every page.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'is_admin' => in_array($request->user()->role, config('freshnest.admin_roles'), true),
                    'is_super' => in_array($request->user()->role, ['super_admin', 'admin'], true),
                    'is_staff' => $request->user()->role === 'staff',
                    'verified' => $request->user()->hasVerifiedEmail(),
                    'avatar_url' => $request->user()->avatarUrl(),
                ] : null,
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'site' => fn () => Setting::publicBusiness(),
            'brand' => fn () => \App\Support\Branding::payload(),
            'statuses' => config('freshnest.statuses'),
            'appUrl' => fn () => url('/'),
        ]);
    }
}
