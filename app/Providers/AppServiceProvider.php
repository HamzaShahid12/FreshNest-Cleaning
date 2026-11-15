<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Appointment times are stored as naive local datetimes in the business timezone.
        $tz = config('freshnest.timezone', 'America/Chicago');
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);

        // Settings are resolved once per request/application instance.
        $this->app->singleton('fn.settings', fn () => Setting::buildMap());
    }

    public function boot(): void
    {
        //
    }
}
