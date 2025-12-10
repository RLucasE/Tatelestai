<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');
    }

    /**
     * Search places by text query
     */
    public function searchPlaces(string $query, ?float $lat = null, ?float $lng = null): array
    {
        try {
            $params = [
                'query' => $query,
                'key' => $this->apiKey,
            ];

            if ($lat && $lng) {
                $params['location'] = "{$lat},{$lng}";
                $params['radius'] = 50000; // 50km radius
            }

            $response = Http::get("{$this->baseUrl}/place/textsearch/json", $params);

            if ($response->failed()) {
                Log::error('Google Places API search failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['status' => 'error', 'results' => []];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Google Places search exception', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'results' => []];
        }
    }

    /**
     * Get detailed information about a place
     */
    public function getPlaceDetails(string $placeId): array
    {
        try {
            $params = [
                'place_id' => $placeId,
                'fields' => 'place_id,name,formatted_address,geometry,formatted_phone_number,opening_hours,photos,rating,types,website,business_status,user_ratings_total',
                'key' => $this->apiKey
            ];

            $response = Http::get("{$this->baseUrl}/place/details/json", $params);

            if ($response->failed()) {
                Log::error('Google Places API details failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['status' => 'error', 'result' => null];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Google Places details exception', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'result' => null];
        }
    }

    /**
     * Get place photo URL
     */
    public function getPhotoUrl(string $photoReference, int $maxWidth = 400): string
    {
        return "{$this->baseUrl}/place/photo?maxwidth={$maxWidth}&photo_reference={$photoReference}&key={$this->apiKey}";
    }

    /**
     * Autocomplete place search
     */
    public function autocomplete(string $input, ?float $lat = null, ?float $lng = null): array
    {
        try {
            $params = [
                'input' => $input,
                'types' => 'establishment',
                'key' => $this->apiKey,
            ];

            if ($lat && $lng) {
                $params['location'] = "{$lat},{$lng}";
                $params['radius'] = 50000;
            }

            $response = Http::get("{$this->baseUrl}/place/autocomplete/json", $params);

            if ($response->failed()) {
                Log::error('Google Places autocomplete failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['status' => 'error', 'predictions' => []];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Google Places autocomplete exception', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'predictions' => []];
        }
    }
}

