<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorImageUploadController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Frontend routes
Route::get('/', [ContentController::class, 'index'])->name('home');

// Preview route (authenticated users only)
Route::get('/preview/{content:slug}', [ContentController::class, 'preview'])
    ->middleware('auth')
    ->name('content.preview');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// RSS Feed
Route::get('/feed', [RssFeedController::class, 'index'])->name('feed.rss');

// Package store/catalog
Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/packages/{package:slug}', [PackageController::class, 'show'])->name('packages.show');

// Dashboard (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-packages', [DashboardController::class, 'index'])->name('my-packages');

    // Video player
    Route::get('/watch/{package:slug}', [PlayerController::class, 'show'])->name('player.show');

    // Checkout thank you
    Route::get('/checkout/thank-you', [CheckoutController::class, 'thankYou'])->name('checkout.thank-you');
});

Route::get('/{content:slug}', [ContentController::class, 'show'])->name('content.show');

// Editor.js image upload endpoint
Route::post('/admin/upload-image', [EditorImageUploadController::class, 'upload'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('admin.upload-image');
