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
use App\Http\Controllers\EcommerceController;

// Public Routes (For Next.js eCommerce)
Route::prefix('v1/shop')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
});

Route::prefix('v1/admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
// Private Routes (For Angular Admin/POS)
Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
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
    Route::get('orders', [EcommerceController::class, 'index']);
    Route::patch('orders/{id}/status', [EcommerceController::class, 'updateStatus']);
    Route::post('orders/{id}/assign', [EcommerceController::class, 'assignToBranch']);
    Route::get('/reports/financial-overview', [ReportController::class, 'financialOverview']);
    Route::get('/reports/inventory-matrix', [ReportController::class, 'inventoryMatrix']);
    Route::get('/reports/daily-closing', [ReportController::class, 'dailyClosing']);
    Route::get('/reports/stock-audit', [ReportController::class, 'stockAudit']);
});