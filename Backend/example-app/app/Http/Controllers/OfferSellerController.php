<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserOffersAction;
use App\Actions\Offers\GetOfferAction;
use App\Actions\Offers\ValidateOfferOwnershipAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\Offers\ValidateProductOwnershipAction;
use App\Actions\Offers\CreateOfferAction;
use App\Exceptions\Product\ProductOwnershipException;

class OfferSellerController extends OfferController
{
    public function __construct(
        private GetUserOffersAction $getUserOffers,
        private GetOfferAction $getOffer,
        private ValidateOfferOwnershipAction $validateOfferOwnership,
        private ValidateProductOwnershipAction $validateProductOwnershipAction,
        private CreateOfferAction $createOfferAction
    ) {}
    public function store(Request $request)
    {
        $request->validate($this->getOfferValidationRules());

        $products = $request->array('products');
        $productIDs = $this->productsToProductsIDs($products);


        try {
            $this->validateProductOwnershipAction->execute($productIDs);
            $offer = $this->createOfferAction->execute($request);

            return response()->json([
                'message' => 'Oferta creada exitosamente',
                'offer' => $offer,
            ], 201);
        } catch (ProductOwnershipException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'product_ids' => $e->context()['product_ids'],
                'establishment_id' => $e->context()['establishment_id'],
                'error' => $e->context()['error'],
            ], $e->getCode());
        }
    }

    public function show(Request $request)
    {
        return response()->json(
            $this->getUserOffers->execute()
        );
    }


    public function offer($offerID)
    {
        $offer = $this->getOffer->execute($offerID);

        if (!$offer) {
            return response()->json([
                'message' => 'Oferta no encontrada'
            ], 404);
        }

        if (!$this->validateOfferOwnership->execute($offer, Auth::user())) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta oferta',
            ], 403);
        }
        $withProducts = true;
        $jsonResponse = $this->getOffer->execute($offer->id, $withProducts);

        return response()->json(
            $jsonResponse,
            200
        );
    }

    public function update(Request $request, $offerID) {}
}
