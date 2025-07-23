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
        // Public listing routes (no auth required)
        Route::prefix('listings')->group(function () {
            Route::get('/', [ListingController::class, 'index']);
            Route::get('/popular', [ListingController::class, 'getPopularListings']);
            Route::get('/recent', [ListingController::class, 'getRecentListings']);
            Route::get('/categories', [ListingController::class, 'getCategories']);
            Route::get('/conditions', [ListingController::class, 'getConditions']);
            Route::get('/{listingId}', [ListingController::class, 'show'])->where('listingId', '[0-9]+');
            Route::get('/slug/{slug}', [ListingController::class, 'showBySlug'])->where('slug', '[a-z0-9\-]+');
            Route::get('/{listingId}/similar', [ListingController::class, 'getSimilarListings'])->where('listingId', '[0-9]+');
        });

        // Protected listing routes (seller authentication required)
        Route::prefix('listings')->middleware(['auth:sanctum', 'seller'])->group(function () {
            Route::post('/', [ListingController::class, 'store']);
            Route::get('/my-listings', [ListingController::class, 'getSellerListings']);
            Route::get('/my-stats', [ListingController::class, 'getSellerStats']);

            Route::prefix('{listingId}')->where(['listingId' => '[0-9]+'])->group(function () {
                Route::get('/edit', [ListingController::class, 'edit']);
                Route::put('/', [ListingController::class, 'update']);
                Route::delete('/', [ListingController::class, 'destroy']);
                Route::post('/publish', [ListingController::class, 'publish']);
                Route::post('/mark-as-sold', [ListingController::class, 'markAsSold']);

                // Image management
                Route::post('/images', [ListingController::class, 'uploadImages']);
                Route::post('/images/reorder', [ListingController::class, 'reorderImages']);
                Route::put('/images/{imageId}', [ListingController::class, 'updateImage'])->where('imageId', '[0-9]+');
                Route::delete('/images/{imageId}', [ListingController::class, 'deleteImage'])->where('imageId', '[0-9]+');
            });
        });

        // Authenticated user routes (buyer features)
        Route::prefix('listings')->middleware('auth:sanctum')->group(function () {
            Route::post('/{listingId}/favorites', [ListingController::class, 'addToFavorites'])->where('listingId', '[0-9]+');
            Route::delete('/{listingId}/favorites', [ListingController::class, 'removeFromFavorites'])->where('listingId', '[0-9]+');
        });
    }
}