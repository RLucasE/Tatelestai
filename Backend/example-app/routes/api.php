<?php

use App\Enums\UserState;
use App\Http\Controllers\AdmOfferController;
use App\Http\Controllers\AdmUserController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerSellController;
use App\Http\Controllers\DashboardExportController;
use App\Http\Controllers\EstablishmentTypeController;
use App\Http\Controllers\FoodEstablishmentController;
use App\Http\Controllers\OfferSellerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicDataController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellerSellController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagement;
use App\Http\Controllers\OfferCustomerController;




Route::middleware(['auth:sanctum', 'role:default'])->post('/select-role', [UserManagement::class, 'chooseRole']);

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::middleware(['user.state:' . UserState::ACTIVE->value])->group(function () {
        Route::get('/offers', [OfferCustomerController::class, 'index']);
        Route::get('/customer-cart', [CustomerCartController::class, 'customerCart']);
        Route::post('/add-to-cart', [CustomerCartController::class, 'addToCart']);
        Route::post('/prepare-purchase', [CustomerSellController::class, 'prepareBuyOffers']);
        Route::post('/buy-offers', [CustomerSellController::class, 'buyOffers']);
        Route::get('/customer/purchases', [CustomerSellController::class, 'customerPurchases']);
        Route::delete('/customer-cart/{offerId}', [CustomerCartController::class, 'removeFromCart']);
        Route::put('/customer-cart/{offerId}', [CustomerCartController::class, 'updateCart']);
        Route::delete('/customer-cart/establishment/{establishment_id}', [CustomerCartController::class, 'clearByEstablishment']);
        Route::get('/purchase-code/{sellNumber}', [CustomerSellController::class, 'getPurchaseCode']);
        Route::get('/customerHistorySell',[CustomerSellController::class, 'historySell']);
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
        Route::get('/sells', [SellerSellController::class, 'sellerSells']);
        Route::put('/my-establishment', [FoodEstablishmentController::class, 'updateMyEstablishment']);
        Route::post('/check-customer-code', [SellerSellController::class, 'checkCustomerCode']);
        Route::post('/complete-sell/{sellNumber}', [SellerSellController::class, 'completeSell']);
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
        Route::get('/adm-establishments/types', [EstablishmentTypeController::class, 'index']);
        Route::post('/adm-establishments/types', [EstablishmentTypeController::class, 'store']);
        Route::get('/adm-establishments/types/{id}', [EstablishmentTypeController::class, 'show']);
        Route::patch('/adm-establishments/types/{id}', [EstablishmentTypeController::class, 'update']);
        Route::delete('/adm-establishments/types/{id}', [EstablishmentTypeController::class, 'destroy']);
        Route::get('/adm-establishments/types-trashed', [EstablishmentTypeController::class, 'trashed']);
        Route::patch('/adm-establishments/types/{id}/restore', [EstablishmentTypeController::class, 'restore']);
        Route::get('/adm/last-sells', [SellController::class, 'lastSells']);
        Route::get('/adm/offer-stats', [AdmOfferController::class, 'offerStats']);
        Route::get('/adm/user-stats', [AdmUserController::class, 'userStats']);
        Route::get('/adm/active-offers-count', [AdmOfferController::class, 'activeOffersCount']);
    });
});
Route::get('/adm/export-dashboard', [DashboardExportController::class, 'export']);


Route::get('/test', function () {
    return "test";
});
