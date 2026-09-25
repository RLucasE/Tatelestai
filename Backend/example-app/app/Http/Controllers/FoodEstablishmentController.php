<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Models\FoodEstablishment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FoodEstablishmentController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $establishment = FoodEstablishment::with(['establishmentType', 'user:id,email'])->find($id);

        if (! $establishment) {
            return response()->json([
                'message' => 'Establecimiento no encontrado',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $establishment->id,
                'name' => $establishment->name,
                'address' => $establishment->address,
                'latitude' => $establishment->latitude !== null ? (float) $establishment->latitude : null,
                'longitude' => $establishment->longitude !== null ? (float) $establishment->longitude : null,
                'establishment_type' => $establishment->establishmentType?->name,
                'phone' => $establishment->phone,
                'phone_number' => $establishment->phone,
                'email' => $establishment->user?->email,
                'description' => $establishment->description,
            ],
        ]);
    }

    public function getMyEstablishment(): JsonResponse
    {
        $user = Auth::user();
        $establishment = $user->foodEstablishment()->with('establishmentType')->first();
        if (! $establishment) {
            return response()->json([
                'message' => 'No establishment found for this user.',
            ], 404);
        }

        return response()->json($establishment);
    }

    public function updateMyEstablishment(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'establishment_type_id' => 'required|integer|exists:establishment_types,id',
        ]);
        $user = Auth::user();
        $establishment = $user->foodEstablishment()->with('establishmentType')->first();
        if (! $establishment) {
            return response()->json([
                'message' => 'No establishment found for this user.',
            ], 404);
        }
        $user->state = UserState::WAITING_FOR_CONFIRMATION->value;
        $user->save();
        $establishment->update($request->only(['name', 'address', 'establishment_type_id']));

        return response()->json([
            'message' => 'Establishment updated successfully.',
            'establishment' => $establishment,
        ]);
    }
}
