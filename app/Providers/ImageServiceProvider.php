<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Images\AwsImageUploadService;
use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AwsImageUploadService::class, function ($app) {
            return new AwsImageUploadService();
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