<?php

namespace App\Http\Controllers;

use App\Enums\UserState;
use App\Enums\VerificationFileType;
use App\Http\Requests\StoreEstablishmentWithVerificationRequest;
use App\Models\EstablishmentType;
use App\Models\EstablishmentVerificationFile;
use App\Models\User;
use App\Services\GooglePlacesService;
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
     * Register establishment with Google Places verification
     */
    public function registerEstablishment(
        StoreEstablishmentWithVerificationRequest $request,
        GooglePlacesService $googlePlacesService
    ): JsonResponse {
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

        if ($placeData['status'] !== 'OK' || ! isset($placeData['result'])) {
            return response()->json([
                'message' => 'No se pudo obtener información del lugar de Google Places.',
                'error' => $placeData['status'] ?? 'UNKNOWN_ERROR',
            ], 400);
        }

        $place = $placeData['result'];

        // Extract coordinates
        $latitude = $place['geometry']['location']['lat'] ?? null;
        $longitude = $place['geometry']['location']['lng'] ?? null;

        // Prepare establishment data (without photo paths)
        $establishmentData = [
            'name' => $place['name'],
            'address' => $place['formatted_address'],
            'google_place_id' => $request->input('google_place_id'),
            'google_place_data' => $place,
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

            // Delete existing verification files
            $existingEstablishment->verificationFiles()->delete();

            $message = 'Establecimiento actualizado exitosamente. Pendiente de verificación.';
        } else {
            // Create new establishment
            $establishment = $user->foodEstablishment()->create($establishmentData);
            $message = 'Establecimiento registrado exitosamente. Pendiente de verificación.';
        }

        // Store verification files
        $verificationFiles = [];
        foreach ($request->verification_files as $fileData) {
            $file = $fileData['file'];
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('establishment_verification/' . $establishment->id, $fileName, 'private');

            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $fileType = VerificationFileType::JPG->value;
            } elseif ($extension === 'png') {
                $fileType = VerificationFileType::PNG->value;
            } elseif ($extension === 'pdf') {
                $fileType = VerificationFileType::PDF->value;
            } else {
                return response()->json([
                    'message' => 'Tipo de archivo no permitido. Solo se permiten JPG, PNG y PDF.',
                ], 400);
            }

            $verificationFile = EstablishmentVerificationFile::create([
                'food_establishment_id' => $establishment->id,
                'file_path' => $path,
                'file_type' => $fileType,
            ]);

            $verificationFiles[] = $verificationFile;
        }

        $user->state = UserState::WAITING_FOR_CONFIRMATION;
        $user->save();

        return response()->json([
            'message' => $message,
            'establishment' => $establishment->load('establishmentType'),
            'verification_files' => $verificationFiles,
        ], $isUpdating ? 200 : 201);
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
