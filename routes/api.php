<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('products/by-slug/{slug}', [ProductController::class, 'showBySlug']);
    Route::apiResource('products', ProductController::class);

    Route::apiResource('categories', CategoryController::class);
    Route::get('brands', [BrandController::class, 'index']);

    Route::get('banners', [BannerController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->middleware('throttle:orders');
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:orders');
        Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('throttle:orders');
        Route::post('orders/{order}/retry-payment', [OrderController::class, 'retryPayment'])->middleware('throttle:orders');
        Route::get('orders/{order}/payment-status', [OrderController::class, 'paymentStatus'])->middleware('throttle:orders');
        Route::post('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->middleware('throttle:orders');
        Route::post('cart/preview', [CartController::class, 'preview'])->middleware('throttle:orders');
        Route::post('coupons/apply', [CouponController::class, 'apply']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist', [WishlistController::class, 'store']);
        Route::delete('wishlist/{product}', [WishlistController::class, 'destroy']);

        Route::get('my/reviews', [ReviewController::class, 'myReviews']);
        Route::post('products/{product}/reviews', [ReviewController::class, 'store']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
    });

    Route::get('products/{product}/reviews', [ReviewController::class, 'index']);
});
