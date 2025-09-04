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
        // Si hay una query de búsqueda, usar Scout
        if ($request->has('search') && !empty(trim($request->get('search')))) {
            $searchQuery = trim($request->get('search'));

            $offers = Offer::search($searchQuery)
                ->where('state', OfferState::ACTIVE->value)
                ->take(20)
                ->get()
                ->filter(function ($offer){
                    return $offer->expiration_datetime >= now();
                } )
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
                        'quantity' => $offer->quantity,
                        'title' => $offer->title,
                        'description' => $offer->description,
                        'expiration_datetime' => $offer->expiration_datetime,
                        'food_establishment_id' => $offer->food_establishment_id,
                        'products' => $offer->products
                    ];
                })
                ->toArray();

            return response()->json($offers);
        }

        // Si no hay búsqueda, usar el action original
        $offers = $this->getCustomerOffers->execute();
        return response()->json($offers);
    }
}
