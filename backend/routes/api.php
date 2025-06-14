<?php

use App\Http\Controllers\BrandsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes cần đăng nhập (tất cả user)
Route::middleware(['auth:sanctum'])->group(function () {
    // Get user info - tất cả user đã đăng nhập đều truy cập được
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Routes chỉ dành cho Admin
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    // Brands management
    Route::apiResource('brands', BrandsController::class);

    // Categories management
    Route::apiResource('categories', CategoriesController::class);

    // Products management
    Route::apiResource('products', ProductsController::class);
});

// Tùy chọn: Routes cho User thường (chỉ xem, không sửa/xóa)
Route::middleware(['auth:sanctum'])->group(function () {
    // User có thể xem danh sách và chi tiết, nhưng không thể tạo/sửa/xóa
    Route::get('/brands', [BrandsController::class, 'index']);
    Route::get('/brands/{id}', [BrandsController::class, 'show']);

    Route::get('/categories', [CategoriesController::class, 'index']);
    Route::get('/categories/{id}', [CategoriesController::class, 'show']);

    Route::get('/products', [ProductsController::class, 'index']);
    Route::get('/products/{id}', [ProductsController::class, 'show']);
});
