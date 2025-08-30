<?php

use App\Enums\UserState;
use App\Http\Controllers\AdmOfferController;
use App\Http\Controllers\AdmUserController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\FoodEstablishmentController;
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
    Route::get('/establishment-types', [PublicDataController::class, 'establishmentTypes'])->middleware(['user.state:' . UserState::REGISTERING->value . ',' . UserState::ACTIVE->value]);
    Route::get('/user', [PublicDataController::class, 'getUser']);
});

Route::middleware(['auth:sanctum', 'role:seller'])->group(function () {
    Route::post('/food-establishment', [UserManagement::class, 'registerEstablishment'])->middleware(['user.state:' . UserState::REGISTERING->value]);
    Route::middleware('user.state:' . UserState::ACTIVE->value)->group(function () {
        Route::get('/my-establishment', [FoodEstablishmentController::class, 'getMyEstablishment']);
        Route::post('/product',  [ProductController::class, 'store']);
        Route::get('/products',  [ProductController::class, 'show']);
        Route::patch('/products/{id}',  [ProductController::class, 'update']);
        Route::delete('/products/{id}',  [ProductController::class, 'destroy']);
        Route::post('/offer', [OfferSellerController::class, 'store']);
        Route::get('my-offers', [OfferSellerController::class, 'show']);
        Route::get('/offer/{offerID}', [OfferSellerController::class, 'offer']);
        Route::patch('/offer/{offerID}', [OfferSellerController::class, 'update']);
        Route::delete('/offer/{offerID}', [OfferSellerController::class, 'destroy']);
        Route::get('/sells', [SellController::class, 'sellerSells']);
        Route::put('/my-establishment', [FoodEstablishmentController::class, 'updateMyEstablishment']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::middleware(['user.state:' . UserState::ACTIVE->value])->group(function () {
        Route::get('/users', [AdmUserController::class, 'index']);
        Route::get('/users/{id}', [AdmUserController::class, 'show']);
        Route::patch('/users/{id}/activate-seller', [AdmUserController::class, 'activateSeller']);
        Route::patch('/users/{id}/deactivate-seller', [AdmUserController::class, 'deactivateSeller']);
        Route::patch('/users/{id}/denie-seller', [AdmUserController::class, 'denySeller']);
        Route::get('/user/{id}/offers', [AdmOfferController::class, 'indexByUser']);
        Route::get('/new-sellers', [AdmUserController::class, 'newSellers']);
        Route::get('/new-sellers/{id}', [AdmUserController::class, 'showNewSeller']);
        Route::get('/adm-offers', [AdmOfferController::class, 'index']);
        Route::patch('/adm-offers/{id}/status', [AdmOfferController::class, 'update']);
        Route::get('/adm-sells', [SellController::class, 'adminSells']);
        Route::get('/adm-sells/{id}', [SellController::class, 'adminSellDetail']);
        Route::get('/adm-customer-purchases/{id}', [SellController::class, 'adminCustomerSells']);
    });
});

Route::get('/test', function () {
    return "test";
});
