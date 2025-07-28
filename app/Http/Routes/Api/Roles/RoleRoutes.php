<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Roles;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Roles\RoleController;
use Illuminate\Support\Facades\Route;

class RoleRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('roles')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [RoleController::class, 'getAllAvailableRoles']);
            Route::get('/current', [RoleController::class, 'getCurrentUserRole']);
            Route::get('/permissions', [RoleController::class, 'permissions']);
            Route::get('/can-upgrade-to-seller', [RoleController::class, 'canUpgradeToSeller']);
            Route::post('/upgrade-to-seller', [RoleController::class, 'upgradeToSeller'])
                ->middleware('role:buyer');
        });
    }
}