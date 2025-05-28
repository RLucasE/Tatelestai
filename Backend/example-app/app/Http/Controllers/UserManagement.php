<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserManagement extends Controller
{
    //
    public function chooseRole(Request $request)
    {

        $request->validate([
            'role' => 'required|in:seller,customer',
        ]);

        $user = Auth::user();
        $role = $request->input('role');

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        if ($user->hasRole(['seller', 'customer'])) {
            return response()->json(['message' => 'You already have this role.'], 400);
        }

        if (!$user->hasRole('unknown_choice')) {
            return response()->json(['message' => 'You can only choose a role if you are in the unknown_choice state.'], 403);
        }

        if ($role === 'seller') {
            $user->syncRoles(["pending_seller"]);
        } else {
            $user->syncRoles(["customer"]);
        }


        return response()->json(['message' => 'Role updated successfully.']);
    }
}
