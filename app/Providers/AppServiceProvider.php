<?php

namespace App\Providers;

use App\Services\Repositories\Auth\AuthDbRepository;
use App\Services\Repositories\Auth\AuthLogicRepository;
use App\Services\Repositories\Listings\ListingRepository;
use App\Services\Repositories\Seller\AppointmentRepository;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthDbRepository::class);
        $this->app->bind(AuthLogicRepository::class);

        $this->app->bind(SellerProfileRepository::class);
        $this->app->bind(AppointmentRepository::class);

        $this->app->bind(ListingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
