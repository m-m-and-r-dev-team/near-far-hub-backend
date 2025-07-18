<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Seller;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Seller\SellerController;
use App\Http\Controllers\Seller\AppointmentController;
use Illuminate\Support\Facades\Route;

class SellerRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('seller')->middleware('auth:sanctum')->group(function () {

            // Seller Profile Routes
            Route::get('/profile', [SellerController::class, 'getProfile']);
            Route::post('/profile', [SellerController::class, 'createProfile']);
            Route::put('/profile', [SellerController::class, 'updateProfile']);
            Route::delete('/profile', [SellerController::class, 'deactivateAccount']);

            // Seller Availability Routes
            Route::get('/availability', [SellerController::class, 'getAvailability']);
            Route::post('/availability', [SellerController::class, 'setAvailability']);

            // Seller Dashboard
            Route::get('/dashboard/stats', [SellerController::class, 'getDashboardStats']);

            // Seller Appointments (as seller)
            Route::get('/appointments', [AppointmentController::class, 'getSellerAppointments']);
            Route::post('/appointments/{appointmentId}/respond', [AppointmentController::class, 'respondToAppointment']);

        });

        Route::prefix('appointments')->middleware('auth:sanctum')->group(function () {

            // Buyer Appointments (as buyer)
            Route::get('/buyer', [AppointmentController::class, 'getBuyerAppointments']);
            Route::post('/book', [AppointmentController::class, 'bookAppointment']);
            Route::post('/{appointmentId}/cancel', [AppointmentController::class, 'cancelAppointment']);

            // Public appointment availability
            Route::get('/seller/{sellerProfileId}/available-slots', [AppointmentController::class, 'getAvailableSlots']);

        });
    }
}