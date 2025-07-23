<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\External\GooglePlacesService;
use App\Services\External\MockGooglePlacesService;
use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GooglePlacesService::class, function ($app) {
            // Use mock service if no API key is configured or in testing
            if (empty(config('services.google.places_api_key')) || app()->environment('testing')) {
                return new MockGooglePlacesService();
            }

            return new GooglePlacesService();
        });
    }

    public function boot(): void
    {
        //
    }
}