<?php

use App\Enums\UserState;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Offer;
use App\Http\Controllers\UserManagement;




Route::middleware(['auth:sanctum', 'role:default'])->post('/select-role', [UserManagement::class, 'chooseRole']);

Route::middleware(['auth:sanctum', 'role:customer'])->get('/offers', function (Request $request) {
    return Offer::with('categories')
        ->latest()
        ->take(10) // Limita a 10 resultados
        ->get();
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
        Route::post('/offer', [OfferController::class, 'store']);
    });
});

Route::get('/test', function () {
    return "test";
});
