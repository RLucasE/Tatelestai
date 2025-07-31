<?php

namespace App\Actions\Offers;

use App\Models\FoodEstablishment;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class GetUserEstablishmentAction
{
    /**
     * Obtiene el establecimiento del usuario autenticado
     *
     * @return FoodEstablishment|null
     */
    public function execute(?User $user = null): ?FoodEstablishment
    {
        if ($user instanceof User) {
            return FoodEstablishment::where('user_id', $user->id)->first();
        } else return FoodEstablishment::where('user_id', Auth::id())->first();
    }
}
