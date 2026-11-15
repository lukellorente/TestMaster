<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Autenticación SPA (cookie de sesión + CSRF). El cliente debe pedir
| GET /sanctum/csrf-cookie antes de login/registro.
*/

// Public routes
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Tests (public)
Route::get('/tests', [TestController::class, 'index']);
Route::get('/tests/stats', [TestController::class, 'stats']);
Route::get('/tests/featured', [TestController::class, 'featured']);
Route::get('/categories', [TestController::class, 'categories']);
Route::get('/tests/{id}', [TestController::class, 'show'])->whereNumber('id');

// Profile (public, datos mínimos)
Route::get('/profile/{id}', [ProfileController::class, 'show'])->whereNumber('id');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:sensitive');

    // Cuenta (RGPD)
    Route::get('/account/export', [AccountController::class, 'export'])->middleware('throttle:sensitive');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('throttle:sensitive');

    // Profile - Avatar
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    // Tests (authenticated)
    Route::post('/tests', [TestController::class, 'store']);
    Route::get('/tests/{id}/edit', [TestController::class, 'edit'])->whereNumber('id');
    Route::put('/tests/{id}', [TestController::class, 'update'])->whereNumber('id');
    Route::delete('/tests/{id}', [TestController::class, 'destroy'])->whereNumber('id');
    Route::get('/tests/{id}/questions', [TestController::class, 'questions'])->whereNumber('id');
    Route::post('/tests/{id}/unlock', [TestController::class, 'unlock'])->whereNumber('id')->middleware('throttle:unlock');

    // Mi progreso (privado)
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/dashboard/attempts', [DashboardController::class, 'attempts']);

    // Administración
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/metrics', [AdminController::class, 'metrics']);
        Route::get('/tests', [AdminController::class, 'tests']);
        Route::patch('/tests/{id}', [AdminController::class, 'updateTest'])->whereNumber('id');
        Route::delete('/tests/{id}', [AdminController::class, 'destroyTest'])->whereNumber('id');
        Route::get('/users', [AdminController::class, 'users']);
        Route::patch('/users/{id}', [AdminController::class, 'updateUser'])->whereNumber('id');
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->whereNumber('id');
        Route::get('/audit', [AdminController::class, 'audit']);
    });

    // Attempts
    Route::post('/attempts/submit', [AttemptController::class, 'submit']);
    Route::get('/attempts/{attemptId}/results', [AttemptController::class, 'results'])->whereNumber('attemptId');
});
