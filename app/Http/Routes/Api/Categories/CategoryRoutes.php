<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Categories;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Categories\CategoryController;
use Illuminate\Support\Facades\Route;

class CategoryRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('categories')->group(function () {

            Route::get('/tree', [CategoryController::class, 'getActiveCategoriesTree']);
            Route::get('/featured', [CategoryController::class, 'getFeaturedCategories']);
            Route::get('/root', [CategoryController::class, 'getRootCategories']);
            Route::get('/search', [CategoryController::class, 'searchCategories']);

            Route::get('/slug/{slug}', [CategoryController::class, 'getCategoryBySlug']);
            Route::get('/{categoryId}/children', [CategoryController::class, 'getCategoryChildren']);
            Route::get('/{categoryId}/path', [CategoryController::class, 'getCategoryPath']);
            Route::get('/{categoryId}/form-fields', [CategoryController::class, 'getCategoryFormFields']);

            Route::post('/suggestions', [CategoryController::class, 'getCategorySuggestions']);
            Route::post('/{categoryId}/validate-attributes', [CategoryController::class, 'validateCategoryAttributes']);
        });

        Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/categories')->group(function () {

            Route::get('/', [CategoryController::class, 'getAllCategories']);
            Route::post('/', [CategoryController::class, 'createCategory']);
            Route::get('/{categoryId}', [CategoryController::class, 'getCategoryById']);
            Route::put('/{categoryId}', [CategoryController::class, 'updateCategory']);
            Route::delete('/{categoryId}', [CategoryController::class, 'deleteCategory']);

            Route::post('/reorder', [CategoryController::class, 'reorderCategories']);
            Route::post('/{categoryId}/toggle-status', [CategoryController::class, 'toggleStatus']);
            Route::get('/{categoryId}/stats', [CategoryController::class, 'getCategoryStats']);
        });
    }
}