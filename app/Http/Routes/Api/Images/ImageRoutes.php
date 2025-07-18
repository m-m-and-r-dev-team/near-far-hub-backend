<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Images;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Images\ImageController;
use Illuminate\Support\Facades\Route;

class ImageRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('images')->middleware('auth:sanctum')->group(function () {

            // Upload routes
            Route::post('/upload', [ImageController::class, 'uploadImage']);
            Route::post('/upload-multiple', [ImageController::class, 'uploadMultipleImages']);

            // Get images
            Route::get('/', [ImageController::class, 'getImages']);
            Route::get('/primary', [ImageController::class, 'getPrimaryImage']);
            Route::get('/stats', [ImageController::class, 'getImageStats']);
            Route::get('/{id}', [ImageController::class, 'getImage'])->where('id', '[0-9]+');

            // Update and manage images
            Route::put('/{id}', [ImageController::class, 'updateImage'])->where('id', '[0-9]+');
            Route::delete('/{id}', [ImageController::class, 'deleteImage'])->where('id', '[0-9]+');
            Route::delete('/bulk', [ImageController::class, 'deleteMultipleImages']);
            Route::delete('/entity', [ImageController::class, 'deleteAllImagesForEntity']);

            // Image management
            Route::post('/{id}/set-primary', [ImageController::class, 'setPrimaryImage'])->where('id', '[0-9]+');
            Route::post('/{id}/toggle-status', [ImageController::class, 'toggleImageStatus'])->where('id', '[0-9]+');
            Route::post('/reorder', [ImageController::class, 'reorderImages']);

        });

        // Public routes (no authentication required)
        Route::prefix('images')->group(function () {

            // Configuration and metadata
            Route::get('/types', [ImageController::class, 'getImageTypes']);
            Route::get('/upload-config/{imageType}', [ImageController::class, 'getUploadConfig']);

        });
    }
}