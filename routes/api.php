<?php

use App\Http\Controllers\Listings\ListingController;
use App\Http\Controllers\Locations\LocationController;
use App\Http\Routes\Api\Auth\AuthRoutes;
use App\Http\Routes\Api\Categories\CategoryRoutes;
use App\Http\Routes\Api\Listings\ListingRoutes;
use App\Http\Routes\Api\Profile\ProfileRoutes;
use App\Http\Routes\Api\Roles\RoleRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Routes\Api\Seller\SellerRoutes;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'api',
        'laravel_version' => app()->version()
    ]);
});

AuthRoutes::api();
RoleRoutes::api();
SellerRoutes::api();
ProfileRoutes::api();

CategoryRoutes::api();
ListingRoutes::api();

Route::prefix('locations')->group(function () {
    Route::get('/suggestions', [LocationController::class, 'getSuggestions']);
    Route::post('/validate', [LocationController::class, 'validateLocation']);
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::get('/popular', [LocationController::class, 'getPopularLocations']);
});

Route::middleware('auth:sanctum')->prefix('images')->group(function () {
    Route::get('/{relatedId}', [ImageController::class, 'getImages']);
    Route::post('/upload/{relatedId}', [ImageController::class, 'uploadImages']);
    Route::patch('/set-primary/{imageId}', [ImageController::class, 'setPrimaryImage']);
    Route::delete('/delete/{imageId}', [ImageController::class, 'deleteImage']);
});

Route::middleware(['auth:sanctum', 'moderator'])->prefix('admin/listings')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Admin listings endpoint - to be implemented']);
    });

    Route::get('/pending', function () {
        return response()->json(['message' => 'Pending listings endpoint - to be implemented']);
    });

    Route::post('/{listingId}/approve', function (int $listingId) {
        return response()->json(['message' => "Listing {$listingId} approved"]);
    });

    Route::post('/{listingId}/reject', function (int $listingId) {
        return response()->json(['message' => "Listing {$listingId} rejected"]);
    });

    Route::post('/{listingId}/suspend', function (int $listingId) {
        return response()->json(['message' => "Listing {$listingId} suspended"]);
    });
});

Route::middleware(['throttle:search'])->group(function () {
    Route::post('/listings/search', [ListingController::class, 'searchListings']);
});

Route::middleware(['throttle:create-listing'])->group(function () {
    Route::middleware('auth:sanctum')->post('/my-listings', [ListingController::class, 'createListing']);
});