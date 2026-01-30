<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEstablishmentWithVerificationRequest;
use App\Models\EstablishmentType;
use App\Models\User;
use App\Services\GmailService;
use App\Services\GooglePlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\CustomerCartController;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Register establishment with Google Places verification
     */
    public function registerEstablishment(
        StoreEstablishmentWithVerificationRequest $request,
        GooglePlacesService $googlePlacesService
    ): JsonResponse
    {
        $user = Auth::user();

        // Check if establishment already exists
        $existingEstablishment = $user->foodEstablishment;
        $isUpdating = $existingEstablishment !== null;

        $establishmentType = EstablishmentType::find($request->input('establishment_type_id'));

        if ($establishmentType->state !== 'active') {
            return response()->json([
                'message' => 'The selected establishment type is not active.',
            ], 400);
        }

        // Get place details from Google Places
        $placeData = $googlePlacesService->getPlaceDetails($request->input('google_place_id'));

        if ($placeData['status'] !== 'OK' || !isset($placeData['result'])) {
            return response()->json([
                'message' => 'No se pudo obtener información del lugar de Google Places.',
                'error' => $placeData['status'] ?? 'UNKNOWN_ERROR'
            ], 400);
        }

        $place = $placeData['result'];

        // Store establishment photo
        $establishmentPhotoPath = $request->file('establishment_photo')->store('establishments', 'public');

        // Store owner selfie
        $ownerSelfiePath = $request->file('owner_selfie')->store('selfies', 'public');

        // Extract coordinates
        $latitude = $place['geometry']['location']['lat'] ?? null;
        $longitude = $place['geometry']['location']['lng'] ?? null;

        // Prepare establishment data
        $establishmentData = [
            'name' => $place['name'],
            'address' => $place['formatted_address'],
            'google_place_id' => $request->input('google_place_id'),
            'google_place_data' => $place,
            'establishment_photo' => $establishmentPhotoPath,
            'owner_selfie' => $ownerSelfiePath,
            'phone' => $place['formatted_phone_number'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'establishment_type_id' => $request->input('establishment_type_id'),
            'verification_status' => 'pending',
        ];

        if ($isUpdating) {
            // Update existing establishment
            $existingEstablishment->update($establishmentData);
            $establishment = $existingEstablishment->fresh();
            $message = 'Establecimiento actualizado exitosamente. Pendiente de verificación.';
        } else {
            // Create new establishment
            $establishment = $user->foodEstablishment()->create($establishmentData);
            $message = 'Establecimiento registrado exitosamente. Pendiente de verificación.';
        }

        $user->state = UserState::WAITING_FOR_CONFIRMATION;
        $user->save();

        return response()->json([
            'message' => $message,
            'establishment' => $establishment->load('establishmentType'),
        ], $isUpdating ? 200 : 201);
    }
}
