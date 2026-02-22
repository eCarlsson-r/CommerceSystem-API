<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;

// routes/api.php
Route::prefix('ecommerce')->group(function () {
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    Route::get('/banners', [BannerController::class, 'publicIndex']);
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}/products', [CategoryController::class, 'products']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/subscribe', [AuthController::class, 'subscribe']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('settings', SettingsController::class);
    Route::apiResource('branches', BranchController::class);
    Route::get('/customers/{id}/history', [CustomerController::class, 'history']);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('/stock-transfers/receive', [StockTransferController::class, 'receive']);
    Route::apiResource('stock-transfers', StockTransferController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('returns', ReturnController::class);
    Route::apiResource('stocks', StockController::class);
    Route::get('/sales/recent', [SaleController::class, 'recent']);
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('banners', BannerController::class);
    Route::get('/reports/financial-overview', [ReportController::class, 'financialOverview']);
    Route::get('/reports/inventory-matrix', [ReportController::class, 'inventoryMatrix']);
    Route::get('/reports/daily-closing', [ReportController::class, 'dailyClosing']);
    Route::get('/reports/stock-audit', [ReportController::class, 'stockAudit']);
    Route::get('/reports/sales-report', [ReportController::class, 'salesReport']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);

    Route::prefix('ecommerce')->group(function () {
        Route::apiResource('cart', CartController::class);
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'myOrders']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'invoice']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::apiResource('wishlist', WishlistController::class);
    });
});
