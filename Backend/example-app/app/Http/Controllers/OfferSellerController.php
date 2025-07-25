<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferSellerController extends OfferController
{
    // /**
    //  * Constructor - Aplicar middleware de autenticación
    //  */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Crear una nueva oferta
     */
    public function store(Request $request)
    {
        $request->validate($this->getOfferValidationRules());

        $products = $request->array('products');
        $productIDs = $this->productsToProductsIDs($products);

        if (!$this->validateProductOwnership($productIDs)) {
            return $this->invalidProductsResponse();
        }

        try {
            $offer = $this->createOfferWithProducts($request);

            return response()->json([
                'message' => 'Oferta creada exitosamente',
                'offer' => $offer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Uno a más productos no pertenecen a tu stablecimiento'
            ], 403);
        }
    }

    public function show(Request $request)
    {
        $userID = Auth::user()->id;
        $offers = $this->userOffers($userID);


        return response()->json(
            $offers
        );
    }


    public function offer($offerID)
    {
        $offer = $this->getOffer($offerID);

        if (!$offer) {
            return response()->json([
                'message' => 'Oferta no encontrada'
            ], 404);
        }

        if (!$this->offerBelongTo($offer, Auth::user())) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta oferta',
            ], 403);
        }

        $jsonResponse = $this->getFullOffer($offer);

        return response()->json(
            $jsonResponse,
            200
        );
    }

    public function update(Request $request, $offerID) {}
}
