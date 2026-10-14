<?php

namespace App\Actions\Establishment;

use App\Enums\TrustScoreCriterion;
use App\Enums\UserState;
use App\Enums\VerificationFileType;
use App\Models\EstablishmentType;
use App\Models\EstablishmentVerificationFile;
use App\Models\FoodEstablishment;
use App\Models\User;
use App\Services\GooglePlacesService;
use Exception;
use Illuminate\Support\Facades\DB;

class RegisterEstablishmentAction
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService
    ) {}

    /**
     * Registra o actualiza un establecimiento comercial con archivos de verificación.
     * Soporta tanto modalidad de Google Places como carga manual con mapa.
     *
     * @param  User  $user
     * @param  array  $data
     * @param  array  $files
     * @return FoodEstablishment
     *
     * @throws Exception
     */
    public function execute(User $user, array $data, array $files): FoodEstablishment
    {
        $establishmentType = EstablishmentType::findOrFail($data['establishment_type_id']);

        if ($establishmentType->state !== 'active') {
            throw new Exception('El tipo de establecimiento seleccionado no está activo.', 400);
        }

        $googlePlaceId = $data['google_place_id'] ?? null;
        $googlePlaceData = null;

        if (! empty($googlePlaceId)) {
            // Modalidad A: Google Places (consulta externa antes de la transacción)
            $placeData = $this->googlePlacesService->getPlaceDetails($googlePlaceId);

            if (($placeData['status'] ?? '') !== 'OK' || ! isset($placeData['result'])) {
                throw new Exception('No se pudo obtener información del lugar de Google Places.', 400);
            }

            $place = $placeData['result'];
            $name = $place['name'];
            $address = $place['formatted_address'];
            $latitude = $place['geometry']['location']['lat'] ?? null;
            $longitude = $place['geometry']['location']['lng'] ?? null;
            $phone = $place['formatted_phone_number'] ?? ($data['phone'] ?? null);
            $googlePlaceData = $place;
        } else {
            // Modalidad B: Carga Manual con Mapa
            $name = $data['name'];
            $address = $data['address'];
            $latitude = $data['latitude'];
            $longitude = $data['longitude'];
            $phone = $data['phone'] ?? null;
        }

        $establishmentData = [
            'name' => $name,
            'address' => $address,
            'google_place_id' => $googlePlaceId,
            'google_place_data' => $googlePlaceData,
            'phone' => $phone,
            'description' => $data['additional_info'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'establishment_type_id' => $data['establishment_type_id'],
            'verification_status' => 'pending',
        ];

        return DB::transaction(function () use ($user, $establishmentData, $files) {
            $existingEstablishment = $user->foodEstablishment;

            if ($existingEstablishment) {
                // Eliminar archivos físicos anteriores si se reenvía el registro
                \Illuminate\Support\Facades\Storage::disk('private')->deleteDirectory('establishment_verification/'.$existingEstablishment->id);

                $existingEstablishment->update($establishmentData);
                $establishment = $existingEstablishment->fresh();
                $establishment->verificationFiles()->delete();
                // Desactivar logs anteriores si se reenvía el registro
                $establishment->trustLogs()->update(['is_active' => false]);
            } else {
                $establishment = $user->foodEstablishment()->create($establishmentData);
            }

            // Almacenar archivos de verificación
            foreach ($files as $fileItem) {
                $file = $fileItem['file'];
                $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('establishment_verification/'.$establishment->id, $fileName, 'private');

                $extension = strtolower($file->getClientOriginalExtension());
                if ($extension === 'jpg' || $extension === 'jpeg') {
                    $fileType = VerificationFileType::JPG->value;
                } elseif ($extension === 'png') {
                    $fileType = VerificationFileType::PNG->value;
                } elseif ($extension === 'pdf') {
                    $fileType = VerificationFileType::PDF->value;
                } else {
                    throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, PNG y PDF.', 400);
                }

                // Categoría de documento si fue provista
                $categoryInput = $fileItem['document_category'] ?? null;
                $documentCategory = null;
                if ($categoryInput && TrustScoreCriterion::tryFrom($categoryInput)) {
                    $documentCategory = TrustScoreCriterion::from($categoryInput);
                }

                EstablishmentVerificationFile::create([
                    'food_establishment_id' => $establishment->id,
                    'file_path' => $path,
                    'file_type' => $fileType,
                    'document_category' => $documentCategory,
                    'status' => 'pending',
                ]);
            }

            $user->state = UserState::WAITING_FOR_CONFIRMATION;
            $user->save();

            return $establishment->load(['establishmentType', 'verificationFiles']);
        });
    }
}
