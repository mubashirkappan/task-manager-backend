<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Outside auth:api so an expired (but still refreshable) token can be exchanged.
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('tasks', TaskController::class);
    // User Dashboard API
    Route::get('/dashboard/user', [DashboardController::class, 'userStats']);

    // Admin-only Dashboard API
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/admin', [DashboardController::class, 'adminStats']);
    });
});
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
