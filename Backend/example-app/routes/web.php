<?php

use Illuminate\Support\Facades\Route;

Route::get('/a', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
