<?php

use App\Models\FoodEstablishment;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('establishment.{establishmentId}', function ($user, $establishmentId) {
    return FoodEstablishment::where('id', $establishmentId)
        ->where('user_id', $user->id)
        ->exists();
});
