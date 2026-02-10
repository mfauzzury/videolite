<?php

use App\Http\Controllers\Api\OrderController;
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

// Public routes
Route::get('/courses', function () {
    // TODO: Implement CourseController@index in Phase 6
    return response()->json(['message' => 'Course catalog endpoint - to be implemented']);
});

Route::get('/courses/{slug}', function ($slug) {
    // TODO: Implement CourseController@show in Phase 6
    return response()->json(['message' => 'Course detail endpoint - to be implemented']);
});

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

    // Enrollments (to be implemented in Phase 6)
    Route::prefix('enrollments')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'My courses endpoint - to be implemented']);
        });
        Route::get('/{id}/curriculum', function ($id) {
            return response()->json(['message' => 'Course curriculum endpoint - to be implemented']);
        });
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
