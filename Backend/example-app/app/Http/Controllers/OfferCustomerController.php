<?php

namespace App\Http\Controllers;

use App\Actions\Offers\Customer\GetCustomerOffersAction;
use App\DTOs\OfferDTO;
use App\DTOs\ProductOfferDTO;
use App\Enums\OfferState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Offer;

class OfferCustomerController extends Controller
{
    public function __construct(
        private GetCustomerOffersAction $getCustomerOffers
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = 20; // Número de ofertas por página

        // Si hay una query de búsqueda, usar Scout
        if ($request->has('search') && !empty(trim($request->get('search')))) {
            $searchQuery = trim($request->get('search'));
            $offers = Offer::search($searchQuery)
                ->where('state', OfferState::ACTIVE->value)
                ->get()
                ->filter(function ($offer){
                    return $offer->expiration_datetime >= now();
                } )
                ->forPage($page, $perPage)
                ->load([
                    'products' => function ($query) {
                        $query->select(
                            'products.id',
                            'products.name',
                            'products.description',
                            'product_offers.price',
                            'product_offers.quantity as product_quantity',
                            'product_offers.offer_id'
                        );
                    },
                    'foodEstablishment' => function ($query) {
                        $query->select('id', 'name', 'address');
                    }
                ])
                ->map(function ($offer) {
                    return $this->transformOfferToDTO($offer);
                });

            return response()->json([
                'data' => $offers->values()->toArray(),
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'has_more' => $offers->count() === $perPage
            ]);
        }

        // Si no hay búsqueda, usar paginación normal
        $offers = Offer::where('state', OfferState::ACTIVE->value)
            ->where('expiration_datetime', '>=', now())
            ->with([
                'products' => function ($query) {
                    $query->select(
                        'products.id',
                        'products.name',
                        'products.description',
                        'product_offers.price',
                        'product_offers.quantity as product_quantity',
                        'product_offers.offer_id'
                    );
                },
                'foodEstablishment' => function ($query) {
                    $query->select('id', 'name', 'address');
                }
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        $transformedOffers = $offers->getCollection()->map(function ($offer) {
            return $this->transformOfferToDTO($offer);
        });

        return response()->json([
            'data' => $transformedOffers,
            'current_page' => $offers->currentPage(),
            'per_page' => $offers->perPage(),
            'has_more' => $offers->hasMorePages()
        ]);
    }

    /**
     * Transform an Offer model to OfferDTO
     */
    private function transformOfferToDTO(Offer $offer): OfferDTO
    {
        $productDTOs = $offer->products->map(function ($product) {
            return new ProductOfferDTO(
                name: $product->name,
                description: $product->description,
                quantity: $product->product_quantity,
                price: $product->price
            );
        })->toArray();

        return new OfferDTO(
            id: $offer->id,
            offer_quantity: $offer->quantity,
            title: $offer->title,
            description: $offer->description,
            expiration_datetime: $offer->expiration_datetime,
            establishment_id: $offer->foodEstablishment->id,
            establishment_name: $offer->foodEstablishment->name,
            establishment_address: $offer->foodEstablishment->address,
            food_establishment_id: $offer->food_establishment_id,
            products: $productDTOs
        );
    }
}
