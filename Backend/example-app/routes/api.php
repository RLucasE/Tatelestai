<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserManagement;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = Auth::user();
    return [
        'id' => $user->id,
        'name' => $user->name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => $user->roles->pluck('name'), // Obtener los roles del usuario
    ];
});

Route::middleware(['auth:sanctum', 'role:unknown_choice'])->post('/select-role', [UserManagement::class, 'chooseRole']);

Route::middleware(['auth:sanctum', 'role:customer'])->get('/offers', function (Request $request) {
    return Offer::with('categories')
        ->latest()
        ->take(10) // Limita a 10 resultados
        ->get();
});


Route::get('/test', function () {
    return "test";
});
