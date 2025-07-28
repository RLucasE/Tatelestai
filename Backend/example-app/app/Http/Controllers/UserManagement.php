<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\CustomerCartController;

class UserManagement extends Controller
{


    public function chooseRole(Request $request)
    {


        $request->validate([
            'role' => 'required|in:seller,customer',
        ]);

        /** @var \App\Models\User */
        $user = Auth::user();
        $role = $request->input('role');
        $changed = false;
        $CartController = new CustomerCartController();


        if ($role === 'seller') {
            $user->syncRoles(["seller"]); // De momento solo le asignamos el rol, falta implementar la lógica de asignación de nuevos vendedores
            $user->state = UserState::REGISTERING;
            $user->save();
            $changed = true;
        }
        if ($role === 'customer') {
            $user->syncRoles(["customer"]);
            $user->state = UserState::ACTIVE;
            $user->save();
            $CartController->asingFirstCart($user);
            $changed = true;
        }

        if (!$changed) {
            return response()->json([
                'message' => 'No changes made to the user role.',
                'current_roles' => $user->roles()->pluck('name'),
            ], 200);
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'new_roles' => $user->roles()->pluck('name'),
        ]);
    }

    public function registerEstablishment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'establishment_type_id' => 'required|integer|exists:establishment_types,id',
        ]);

        /** @var \App\Models\User */
        $user = Auth::user();

        if ($user->foodEstablishment()->exists()) {
            return response()->json([
                'message' => 'You already have a registered establishment.',
            ], 400);
        }

        $establishment = $user->foodEstablishment()->create([
            'establishment_type_id' => $request->input('establishment_type_id'),
        ]);

        $user->state = UserState::WAITING_FOR_CONFIRMATION;
        $user->save();

        return response()->json([
            'message' => 'Establishment registered successfully.',
            'establishment' => $establishment,
        ]);
    }
}
