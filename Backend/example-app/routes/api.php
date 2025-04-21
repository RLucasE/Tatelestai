<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Offer;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->get('/offers', function (Request $request) {
    return Offer::with('categories')
        ->latest()
        ->take(10) // Limita a 10 resultados
        ->get();
});


Route::get('/test', function () {
    return ['Users' => User::all()];
});
