<?php

namespace App\Providers;

use App\Services\BrandingService;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, function () {
            return new SettingsService(\App\Models\Tenant::current());
        });

        $this->app->singleton(BrandingService::class, function () {
            return new BrandingService(\App\Models\Tenant::current());
        });
    }

    public function boot(): void
    {
        // Inertia pages read single API resources flat (e.g. booking.booking_number),
        // so drop the default "data" wrapper. Paginated collections keep data/meta/links.
        JsonResource::withoutWrapping();

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('inquiry', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
