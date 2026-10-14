<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Http\Requests\StoreEstablishmentWithVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagement extends Controller
{
    public function chooseRole(Request $request): JsonResponse
    {

        $request->validate([
            'role' => 'required|in:seller,customer',
        ]);

        $user = User::find(Auth::id());
        $role = $request->input('role');
        $changed = false;

        if ($role === 'seller') {
            $user->syncRoles(['seller']);
            $user->state = UserState::REGISTERING;
            $user->save();
            $changed = true;
        }
        if ($role === 'customer') {
            $user->syncRoles(['customer']);
            $user->state = UserState::ACTIVE;
            $user->save();
            app(CustomerCartController::class)->asingFirstCart($user);
            $changed = true;
        }

        if (! $changed) {
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

    /**
     * Register establishment with Google Places or manual location verification
     */
    public function registerEstablishment(
        StoreEstablishmentWithVerificationRequest $request,
        \App\Actions\Establishment\RegisterEstablishmentAction $registerEstablishmentAction
    ): JsonResponse {
        try {
            $user = Auth::user();
            $isUpdating = $user->foodEstablishment !== null;

            $establishment = $registerEstablishmentAction->execute(
                $user,
                $request->validated(),
                $request->verification_files
            );

            $message = $isUpdating
                ? 'Establecimiento actualizado exitosamente. Pendiente de verificación.'
                : 'Establecimiento registrado exitosamente. Pendiente de verificación.';

            return response()->json([
                'message' => $message,
                'establishment' => $establishment,
                'verification_files' => $establishment->verificationFiles,
            ], $isUpdating ? 200 : 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 400);
        }
    }

    /**
     * Cancel seller registration and change role back to default
     */
    public function cancelSellerRegistration(): JsonResponse
    {
        $user = Auth::user();

        // Sync roles back to default
        $user->syncRoles(['default']);
        $user->state = UserState::SELECTING;
        $user->save();

        return response()->json([
            'message' => 'Registro de vendedor cancelado. Rol cambiado a default exitosamente.',
            'new_roles' => $user->roles()->pluck('name'),
            'state' => $user->state,
        ]);
    }
}
