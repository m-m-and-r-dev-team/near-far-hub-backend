<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Profile;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

class ProfileRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [UserController::class, 'getProfile']);
            Route::put('/', [UserController::class, 'updateProfile']);
        });
    }
}