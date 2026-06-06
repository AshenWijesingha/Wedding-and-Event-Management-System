<?php

namespace App\Providers;

use App\Services\BrandingService;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, function () {
            return new SettingsService(app('currentTenant'));
        });

        $this->app->singleton(BrandingService::class, function () {
            return new BrandingService(app('currentTenant'));
        });
    }

    public function boot(): void
    {
        //
    }
}
