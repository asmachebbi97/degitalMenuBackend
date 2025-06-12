<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);
Route::get('/restaurants/{id}/menu-items', [MenuItemController::class, 'index']);
Route::post('/checkUserExist', [AuthController::class, 'checkUserExist']);
Route::post('/resetPassword', [AuthController::class, 'resetPassword']);
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/UpdateUser/{id}', [AuthController::class, 'updateUser']);
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/restaurants/{id}/toggle-active', [RestaurantController::class, 'toggleActive']);
        Route::get('/admin/statistics', [RestaurantController::class, 'getAllRestaurantsStatistics']);

    });

    // Restaurant owner routes
    Route::middleware('role:restaurant')->group(function () {
        Route::post('/restaurants', [RestaurantController::class, 'store']);
        Route::put('/restaurants/{id}', [RestaurantController::class, 'update']);
        Route::delete('/restaurants/{id}', [RestaurantController::class, 'destroy']);
        Route::post('/restaurants/{id}/toggle-available', [RestaurantController::class, 'toggleAvailable']);
        Route::get('/users/{id}/restaurants', [RestaurantController::class, 'getByOwner']);
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);
        Route::get('/restaurants/{id}/orders', [OrderController::class, 'getByRestaurant']);
        Route::get('/restaurants/{id}/statistics', [RestaurantController::class, 'getStatistics']);
        Route::get('/restaurants/{id}/Ownerstatistics', [RestaurantController::class, 'getStatisticsByOwner']);

    });

    // Customer routes
    Route::middleware('role:customer')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/users/{id}/orders', [OrderController::class, 'getByCustomer']);
        Route::get('/users/{id}/orders', [OrderController::class, 'getByCustomer']);
        Route::post('/addToCart', [OrderController::class, 'addToCart']);
        Route::post('/updateCartItem/{id}', [OrderController::class, 'updateCartItem']);
        Route::delete('/deleteCartItem/{id}', [OrderController::class, 'deleteCartItem']);
        Route::post('/changeCartStatus/{id}', [OrderController::class, 'changeCartStatus']);
        Route::post('/clearCart/{id}', [OrderController::class, 'changeCartStatus']);
        Route::post('/addOrderHistory', [OrderController::class, 'addOrderHistory']);
        Route::post('/addDeliveryInfo/{id}', [OrderController::class, 'addDeliveryInfo']);



    });

    // Restaurant owner and admin routes
    Route::middleware('role:restaurant,admin')->group(function () {
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
    });
});
