<?php

namespace App\Http\Controllers;

use App\Actions\Offers\SearchOffersAction;
use App\Enums\OfferState;
use App\Http\Resources\PackOfferResource;
use App\Models\Offer;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackCustomerController extends Controller
{
    public function __construct(
        private readonly SearchOffersAction $searchOffersAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $perPage = $request->filled('per_page')
            ? max(1, min((int) $request->get('per_page'), 100))
            : 20;

        $hasSearch = $request->filled('search');
        $hasGeo = $request->filled('lat') && $request->filled('lng');
        $hasEstablishment = $request->filled('food_establishment_id');

        if (($hasSearch || $hasGeo) && ! $hasEstablishment) {
            $searchQueryDTO = new SearchQueryDTO(
                query: $hasSearch ? trim($request->get('search')) : '',
                page: $page,
                perPage: $perPage,
                latitude: $hasGeo ? (float) $request->get('lat') : null,
                longitude: $hasGeo ? (float) $request->get('lng') : null,
                radiusKm: $request->filled('radius') ? (float) $request->get('radius') : 5.0,
            );

            $packs = $this->searchOffersAction->execute($searchQueryDTO);

            return response()->json([
                'data' => PackOfferResource::collection($packs)->resolve(),
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => $packs->count() === $perPage,
            ]);
        }

        $query = Offer::where('state', OfferState::ACTIVE->value)
            ->where('expiration_datetime', '>=', now())
            ->with('foodEstablishment:id,name,address,latitude,longitude');

        if ($hasEstablishment) {
            $query->where('food_establishment_id', (int) $request->get('food_establishment_id'));
        }

        $packs = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => PackOfferResource::collection($packs->getCollection())->resolve(),
            'current_page' => $packs->currentPage(),
            'per_page' => $packs->perPage(),
            'has_more' => $packs->hasMorePages(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pack = Offer::with('foodEstablishment:id,name,address,latitude,longitude')
            ->where('state', OfferState::ACTIVE->value)
            ->where('expiration_datetime', '>=', now())
            ->find($id);

        if (! $pack) {
            return response()->json([
                'message' => 'Pack no encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Pack obtenido exitosamente',
            'data' => new PackOfferResource($pack),
        ], 200);
    }
}
