<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Tests (public)
Route::get('/tests', [TestController::class, 'index']);
Route::get('/tests/stats', [TestController::class, 'stats']);
Route::get('/categories', [TestController::class, 'categories']);
Route::get('/tests/{id}', [TestController::class, 'show']);

// Profile (public)
Route::get('/profile/{id}', [ProfileController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile - Avatar
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    // Tests (authenticated)
    Route::post('/tests', [TestController::class, 'store']);
    Route::get('/tests/{id}/edit', [TestController::class, 'edit']);
    Route::put('/tests/{id}', [TestController::class, 'update']);
    Route::delete('/tests/{id}', [TestController::class, 'destroy']);
    Route::get('/tests/{id}/questions', [TestController::class, 'questions']);

    // Attempts
    Route::post('/attempts/submit', [AttemptController::class, 'submit']);
    Route::get('/attempts/{attemptId}/results', [AttemptController::class, 'results']);
});
