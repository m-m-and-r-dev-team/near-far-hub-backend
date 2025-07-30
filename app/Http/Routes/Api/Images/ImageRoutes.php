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
        Route::middleware('auth:sanctum')->prefix('images')->group(function () {
            Route::get('/{relatedId}', [ImageController::class, 'getImages'])
                ->where('relatedId', '[0-9]+');

            Route::post('/upload/{relatedId}', [ImageController::class, 'uploadImages'])
                ->where('relatedId', '[0-9]+');

            Route::patch('/set-primary/{imageId}', [ImageController::class, 'setPrimaryImage'])
                ->where('imageId', '[0-9]+');

            Route::delete('/delete/{imageId}', [ImageController::class, 'deleteImage'])
                ->where('imageId', '[0-9]+');
        });
    }
}