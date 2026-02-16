<?php

use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Webhook routes (no auth, no CSRF)
Route::post('/webhooks/billplz', [WebhookController::class, 'billplz'])->name('webhooks.billplz');

// Public routes - Packages
Route::get('/packages', [PackageController::class, 'index'])->name('api.packages.index');
Route::get('/packages/{package:slug}', [PackageController::class, 'show'])->name('api.packages.show');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('/', [OrderController::class, 'store'])->name('api.orders.store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('api.orders.show');
    });

    // Enrollments
    Route::prefix('enrollments')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index'])->name('api.enrollments.index');
        Route::get('/{enrollment}/curriculum', [EnrollmentController::class, 'curriculum'])->name('api.enrollments.curriculum');
    });

    // Video streaming & PDF downloads
    Route::prefix('lessons/{lesson}')->group(function () {
        Route::get('/stream', [VideoController::class, 'stream'])
            ->name('api.lessons.stream')
            ->middleware('throttle:60,1'); // Rate limit: 60 requests per minute
        Route::get('/download-pdf', [VideoController::class, 'downloadPdf'])
            ->name('api.lessons.download-pdf');
    });
});
