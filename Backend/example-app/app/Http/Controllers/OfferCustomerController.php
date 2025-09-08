<?php

namespace App\Http\Controllers;

use App\Actions\Offers\Customer\GetCustomerOffersAction;
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
                ->load(['products' => function ($query) {
                    $query->select(
                        'products.id',
                        'products.name',
                        'products.description',
                        'product_offers.price',
                        'product_offers.quantity as product_quantity',
                        'product_offers.offer_id'
                    );
                }])
                ->map(function ($offer) {
                    return [
                        'id' => $offer->id,
                        'offer_quantity' => $offer->quantity,
                        'title' => $offer->title,
                        'description' => $offer->description,
                        'expiration_datetime' => $offer->expiration_datetime,
                        'food_establishment_id' => $offer->food_establishment_id,
                        'products' => $offer->products
                    ];
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
            ->with(['products' => function ($query) {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.description',
                    'product_offers.price',
                    'product_offers.quantity as product_quantity',
                    'product_offers.offer_id'
                );
            }])
            ->paginate($perPage, ['*'], 'page', $page);

        $transformedOffers = $offers->getCollection()->map(function ($offer) {
            return [
                'id' => $offer->id,
                'offer_quantity' => $offer->quantity,
                'title' => $offer->title,
                'description' => $offer->description,
                'expiration_datetime' => $offer->expiration_datetime,
                'food_establishment_id' => $offer->food_establishment_id,
                'products' => $offer->products
            ];
        });

        return response()->json([
            'data' => $transformedOffers,
            'current_page' => $offers->currentPage(),
            'per_page' => $offers->perPage(),
            'has_more' => $offers->hasMorePages()
        ]);
    }
}
