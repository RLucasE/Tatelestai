<?php

namespace App\Http\Controllers;

use App\Enums\EstablishmentTypeState;
use App\Http\Controllers\Controller;
use App\Models\EstablishmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicDataController extends Controller
{
    //
    public function establishmentTypes()
    {
        return response()->json(
            EstablishmentType::select('id', 'name')
                ->where('state', EstablishmentTypeState::ACTIVE->value)
                ->orderBy('id')
                ->get()
        );
    }

    public function getUser(Request $request)
    {
        $user = Auth::user();
        return [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'), // Obtener los roles del usuario
            'state' => $user->state, // Obtener el estado del usuario
        ];
    }
}
