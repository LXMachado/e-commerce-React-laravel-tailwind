<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::post('/register', RegisterController::class)->name('auth.register');
    Route::post('/login', LoginController::class)->name('auth.login');
    Route::post('/logout', LogoutController::class)->name('auth.logout')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->get('/user', UserController::class);

// Catalog API Routes (Public)
Route::prefix('catalog')->name('catalog.')->group(function () {
    // Categories
    Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/tree', [App\Http\Controllers\Api\CategoryController::class, 'tree'])->name('categories.tree');
    Route::get('/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'show'])->name('categories.show');

    // Products
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [App\Http\Controllers\Api\ProductController::class, 'show'])->name('products.show');
    Route::get('/categories/{categorySlug}/products', [App\Http\Controllers\Api\ProductController::class, 'byCategory'])->name('products.by-category');

    // Product Variants
    Route::get('/variants', [App\Http\Controllers\Api\ProductVariantController::class, 'index'])->name('variants.index');
    Route::get('/variants/{variant}', [App\Http\Controllers\Api\ProductVariantController::class, 'show'])->name('variants.show');
    Route::get('/products/{productId}/variants', [App\Http\Controllers\Api\ProductVariantController::class, 'byProduct'])->name('variants.by-product');
});

// Protected Catalog API Routes (Admin/Manager only)
Route::middleware(['auth:sanctum'])->prefix('admin/catalog')->name('admin.catalog.')->group(function () {
    // Category Management
    Route::post('/categories', [App\Http\Controllers\Api\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Product Management
    Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [App\Http\Controllers\Api\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->name('products.destroy');

    // Product Variant Management
    Route::post('/variants', [App\Http\Controllers\Api\ProductVariantController::class, 'store'])->name('variants.store');
    Route::put('/variants/{variant}', [App\Http\Controllers\Api\ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('/variants/{variant}', [App\Http\Controllers\Api\ProductVariantController::class, 'destroy'])->name('variants.destroy');
    Route::patch('/variants/{variant}/stock', [App\Http\Controllers\Api\ProductVariantController::class, 'updateStock'])->name('variants.update-stock');
});