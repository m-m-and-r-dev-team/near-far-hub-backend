<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Listings;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Listings\ListingController;
use Illuminate\Support\Facades\Route;

class ListingRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('listings')->group(function () {

            Route::get('/', [ListingController::class, 'getActiveListings']);
            Route::post('/search', [ListingController::class, 'searchListings']);
            Route::get('/featured', [ListingController::class, 'getFeaturedListings']);
            Route::get('/popular', [ListingController::class, 'getPopularListings']);
            Route::get('/recent', [ListingController::class, 'getRecentListings']);

            Route::get('/slug/{slug}', [ListingController::class, 'getListingBySlug']);
            Route::get('/{listingId}/similar', [ListingController::class, 'getSimilarListings']);

            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/{listingId}/contact', [ListingController::class, 'contactSeller']);
            });
        });

        Route::middleware('auth:sanctum')->prefix('my-listings')->group(function () {

            Route::get('/', [ListingController::class, 'getSellerListings']);
            Route::get('/stats', [ListingController::class, 'getSellerStats']);
            Route::post('/', [ListingController::class, 'createListing']);

            Route::get('/{listingId}', [ListingController::class, 'getListingById']);
            Route::put('/{listingId}', [ListingController::class, 'updateListing']);
            Route::delete('/{listingId}', [ListingController::class, 'deleteListing']);

            Route::post('/{listingId}/mark-sold', [ListingController::class, 'markAsSold']);
            Route::post('/{listingId}/renew', [ListingController::class, 'renewListing']);
            Route::post('/{listingId}/feature', [ListingController::class, 'makeFeatured']);
        });
    }
}
