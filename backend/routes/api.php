<?php

use App\Http\Controllers\Api\Admin\ShipmentStatusController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ShipmentController;
use Illuminate\Support\Facades\Route;

/*
| Every route in this file automatically gets:
|   - the /api prefix
|   - the 'api' middleware group (which includes our ForceJsonResponse)
|   - the global middleware (AssignRequestId)
*/

// Public routes, with rate limiting (route middleware 'throttle').
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Everything below needs a valid Sanctum token: "Authorization: Bearer <token>".
Route::middleware('auth:sanctum')->group(function () {

    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('dashboard', DashboardController::class);

    Route::post('shipments/quote', [ShipmentController::class, 'quote']);
    Route::get('shipments', [ShipmentController::class, 'index']);
    Route::post('shipments', [ShipmentController::class, 'store']);
    Route::get('shipments/{shipment}', [ShipmentController::class, 'show']);

    // Authorization as route middleware: runs ShipmentPolicy::cancel() before the controller.
    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])
        ->can('cancel', 'shipment');

    // Admin-only group: route middleware with a parameter.
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::patch('shipments/{shipment}/status', ShipmentStatusController::class);
    });
});
