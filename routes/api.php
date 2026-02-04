<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Routes (For Next.js eCommerce)
Route::prefix('v1/shop')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
});

// Private Routes (For Angular Admin/POS)
Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);
    Route::get('/reports/daily-closing', [ReportController::class, 'getDailyClosingReport']);
});