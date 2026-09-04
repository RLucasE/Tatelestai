<?php

namespace App\Http\Controllers;

use App\Actions\Offers\SearchOffersAction;
use App\DTOs\OfferDTO;
use App\DTOs\ProductOfferDTO;
use App\Enums\OfferState;
use App\Models\Offer;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferCustomerController extends Controller
{
    public function __construct(
        private readonly SearchOffersAction $searchOffersAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = 20; // Número de ofertas por página

        // Si hay una query de búsqueda, usar el servicio de búsqueda
        if ($request->has('search') && ! empty(trim($request->get('search')))) {
            $searchQuery = trim($request->get('search'));
            $searchQueryDTO = new SearchQueryDTO(
                query: $searchQuery,
                page: (int) $page,
                perPage: $perPage,
                latitude: $request->filled('lat') ? (float) $request->get('lat') : null,
                longitude: $request->filled('lng') ? (float) $request->get('lng') : null,
                radiusKm: $request->filled('radius') ? (float) $request->get('radius') : 5.0,
            );

            $offers = $this->searchOffersAction->execute($searchQueryDTO)
                ->map(function ($offer) {
                    return $this->transformOfferToDTO($offer);
                });

            return response()->json([
                'data' => $offers->values()->toArray(),
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'has_more' => $offers->count() === $perPage,
            ]);
        }

        // Si no hay búsqueda, usar paginación normal
        $offers = Offer::where('state', OfferState::ACTIVE->value)
            ->where('expiration_datetime', '>=', now())
            ->with([
                'fullProducts',
                'foodEstablishment' => function ($query) {
                    $query->select('id', 'name', 'address', 'latitude', 'longitude');
                },
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        $transformedOffers = $offers->getCollection()->map(function ($offer) {
            return $this->transformOfferToDTO($offer);
        });

        return response()->json([
            'data' => $transformedOffers,
            'current_page' => $offers->currentPage(),
            'per_page' => $offers->perPage(),
            'has_more' => $offers->hasMorePages(),
        ]);
    }

    /**
     * Transform an Offer model to OfferDTO
     *
     * @throws \Exception
     */
    private function transformOfferToDTO(Offer $offer): OfferDTO
    {
        try {

            $productDTOs = $offer->fullProducts->map(function ($product) {
                return new ProductOfferDTO(
                    name: $product->name ?? '',
                    description: $product->description ?? '',
                    quantity: $product->pivot->quantity ?? 0,
                    price: $product->pivot->price ?? 0.0,
                    expiration_date: $product->pivot->expiration_date ?? ''
                );
            })->toArray();

            return new OfferDTO(
                id: $offer->id,
                offer_quantity: $offer->quantity,
                title: $offer->title,
                description: $offer->description,
                expiration_datetime: $offer->expiration_datetime ?? '',
                establishment_id: $offer->foodEstablishment->id,
                establishment_name: $offer->foodEstablishment->name,
                establishment_address: $offer->foodEstablishment->address,
                food_establishment_id: $offer->food_establishment_id,
                products: $productDTOs,
                establishment_latitude: $offer->foodEstablishment?->latitude !== null ? (float) $offer->foodEstablishment->latitude : null,
                establishment_longitude: $offer->foodEstablishment?->longitude !== null ? (float) $offer->foodEstablishment->longitude : null,
            );
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
