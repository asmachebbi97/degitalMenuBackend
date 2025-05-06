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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/restaurants/{id}/toggle-active', [RestaurantController::class, 'toggleActive']);
    });

    // Restaurant owner routes
    Route::middleware('role:restaurant')->group(function () {
        Route::post('/restaurants', [RestaurantController::class, 'store']);
        Route::put('/restaurants/{id}', [RestaurantController::class, 'update']);
        Route::get('/users/{id}/restaurants', [RestaurantController::class, 'getByOwner']);
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);
        Route::get('/restaurants/{id}/orders', [OrderController::class, 'getByRestaurant']);
        Route::get('/restaurants/{id}/statistics', [RestaurantController::class, 'getStatistics']);
    });

    // Customer routes
    Route::middleware('role:customer')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/users/{id}/orders', [OrderController::class, 'getByCustomer']);
    });

    // Restaurant owner and admin routes
    Route::middleware('role:restaurant,admin')->group(function () {
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
