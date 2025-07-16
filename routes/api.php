<?php

use App\Http\Routes\Api\Auth\AuthRoutes;
use App\Http\Routes\Api\Roles\RoleRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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