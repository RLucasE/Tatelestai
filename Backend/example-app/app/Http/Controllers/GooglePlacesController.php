<?php

namespace App\Http\Controllers;

use App\Services\GooglePlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GooglePlacesController extends Controller
{
    protected GooglePlacesService $googlePlacesService;

    public function __construct(GooglePlacesService $googlePlacesService)
    {
        $this->googlePlacesService = $googlePlacesService;
    }

    /**
     * Search places by text query
     */
    public function searchPlaces(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $results = $this->googlePlacesService->searchPlaces(
            $request->input('query'),
            $request->input('lat'),
            $request->input('lng')
        );

        if ($results['status'] === 'error') {
            return response()->json([
                'message' => 'Error al buscar lugares',
                'results' => []
            ], 500);
        }

        return response()->json($results);
    }

    /**
     * Get place details by place ID
     */
    public function getPlaceDetails(string $placeId): JsonResponse
    {
        $details = $this->googlePlacesService->getPlaceDetails($placeId);

        if ($details['status'] === 'error' || $details['status'] !== 'OK') {
            return response()->json([
                'message' => 'No se pudo obtener información del lugar',
                'error' => $details['status'] ?? 'UNKNOWN_ERROR'
            ], 404);
        }

        return response()->json($details);
    }

    /**
     * Autocomplete search
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'input' => 'required|string|min:2',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $results = $this->googlePlacesService->autocomplete(
            $request->input('input'),
            $request->input('lat'),
            $request->input('lng')
        );

        if ($results['status'] === 'error') {
            return response()->json([
                'message' => 'Error en autocompletado',
                'predictions' => []
            ], 500);
        }

        return response()->json($results);
    }
}

