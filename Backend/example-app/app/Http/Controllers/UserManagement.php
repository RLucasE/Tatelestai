<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\CustomerCartController;

class UserManagement extends Controller
{


    public function chooseRole(Request $request):JsonResponse
    {

        $request->validate([
            'role' => 'required|in:seller,customer',
        ]);

        $user = User::find(Auth::id());
        $role = $request->input('role');
        $changed = false;

        if ($role === 'seller') {
            $user->syncRoles(["seller"]);
            $user->state = UserState::REGISTERING;
            $user->save();
            $changed = true;
        }
        if ($role === 'customer') {
            $user->syncRoles(["customer"]);
            $user->state = UserState::ACTIVE;
            $user->save();
            app(CustomerCartController::class)->asingFirstCart($user);
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
