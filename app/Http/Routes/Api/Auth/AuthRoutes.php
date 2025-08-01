<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Auth;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

class AuthRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
            Route::get('/user', [AuthController::class, 'getCurrentUser'])->middleware('auth:sanctum');
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        });
    }
}