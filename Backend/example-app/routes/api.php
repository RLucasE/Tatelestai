<?php

use App\Enums\UserState;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\OfferSellerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicDataController;
use App\Http\Controllers\SellController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagement;
use App\Http\Controllers\OfferCustomerController;




Route::middleware(['auth:sanctum', 'role:default'])->post('/select-role', [UserManagement::class, 'chooseRole']);

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::middleware(['user.state:' . UserState::ACTIVE->value])->group(function () {
        Route::get('/offers', [OfferCustomerController::class, 'index']);
        Route::get('customer-cart', [CustomerCartController::class, 'customerCart']);
        Route::post('/add-to-cart', [CustomerCartController::class, 'addToCart']);
        Route::post('/buy-offers',[SellController::class, 'buyOffers']);
        Route::get('/customer/purchases', [SellController::class, 'customerPurchases']);
        Route::delete('/customer-cart/{offerId}', [CustomerCartController::class, 'removeFromCart']);
        Route::put('/customer-cart/{offerId}', [CustomerCartController::class, 'updateCart']);
        Route::delete('/customer-cart/establishment/{establishment_id}', [CustomerCartController::class, 'clearByEstablishment']);
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/establishment-types', [PublicDataController::class, 'establishmentTypes'])->middleware(['user.state:' . UserState::REGISTERING->value]);
    Route::get('/user', [PublicDataController::class, 'getUser']);
});

Route::middleware(['auth:sanctum', 'role:seller'])->group(function () {
    Route::post('/food-establishment', [UserManagement::class, 'registerEstablishment'])->middleware(['user.state:' . UserState::REGISTERING->value]);
    Route::middleware('user.state:' . UserState::ACTIVE->value)->group(function () {
        Route::post('/product',  [ProductController::class, 'store']);
        Route::get('/products',  [ProductController::class, 'show']);
        Route::patch('/products/{id}',  [ProductController::class, 'update']);
        Route::delete('/products/{id}',  [ProductController::class, 'destroy']);
        Route::post('/offer', [OfferSellerController::class, 'store']);
        Route::get('my-offers', [OfferSellerController::class, 'show']);
        Route::get('/offer/{offerID}', [OfferSellerController::class, 'offer']);
        Route::patch('/offer/{offerID}', [OfferSellerController::class, 'update']);
        Route::get('/sells', [SellController::class, 'sellerSells']);
    });
});

Route::get('/test', function () {
    return "test";
});
