<?php

use Illuminate\Support\Facades\Route;

Route::get('/a', function () {
    return ['Laravel' => app()->version()];
});
