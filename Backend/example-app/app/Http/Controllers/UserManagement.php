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

        /** @var \App\Models\User */
        $user = Auth::user();
        $role = $request->input('role');
        $changed = false;

        if ($role === 'seller') {
            $user->syncRoles(["seller"]); // De momento solo le asignamos el rol, falta implementar la lógica de asignación de nuevos vendedores
            $changed = true;
        }
        if ($role === 'customer') {
            $user->syncRoles(["customer"]);
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
}
