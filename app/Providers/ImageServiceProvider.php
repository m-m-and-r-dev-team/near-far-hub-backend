<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Images\ImageUploadService;
use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageUploadService::class, function ($app) {
            return new ImageUploadService();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/images.php' => config_path('images.php'),
            ], 'config');
        }
    }
}