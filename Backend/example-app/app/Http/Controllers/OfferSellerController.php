<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserOffersAction;
use App\Actions\Offers\GetOfferAction;
use App\Actions\Offers\ValidateOfferOwnershipAction;
use App\Actions\Offers\ValidateExpirationDatesAction;
use App\DTOs\CreateNewOfferDTO;
use App\DTOs\PrepareOfferDTO;
use App\Enums\OfferState;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\Offers\ValidateProductOwnershipAction;
use App\Actions\Offers\CreateOfferAction;
use App\Exceptions\Product\ProductOwnershipException;
use Illuminate\Http\Response;

class OfferSellerController extends OfferController
{
    public function __construct(
        private GetUserOffersAction $getUserOffers,
        private GetOfferAction $getOffer,
        private ValidateOfferOwnershipAction $validateOfferOwnership,
        private ValidateProductOwnershipAction $validateProductOwnershipAction,
        private CreateOfferAction $createOfferAction,
        private ValidateExpirationDatesAction $validateExpirationDatesAction
    ) {}
    public function store(Request $request)
    {
        $request->validate($this->getOfferValidationRules());
        $offerDTO = CreateNewOfferDTO::fromRequest($request);

        try {
            // Validar fechas de expiración
            $this->validateExpirationDatesAction->execute($offerDTO);

            $products = $request->array('products');
            $productIDs = $this->productsToProductsIDs($products);

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
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request)
    {
        return response()->json(
            $this->getUserOffers->execute(20)
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

    public function destroy($offerID)
    {
        try {
            $offer = $this->getOffer->execute($offerID);
            !$this->validateOfferOwnership->execute($offer, Auth::user());
        }catch (ProductOwnershipException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'product_ids' => $e->context()['product_ids'],
                'establishment_id' => $e->context()['establishment_id'],
                'error' => $e->context()['error'],
            ], $e->getCode());
        }catch (\Exception $e) {
            return response()->json([
                $e
            ], 404);
        }

        $offer->state = OfferState::INACTIVE;
        $offer->save();
        return response()->json([
            'message' => 'Oferta eliminada correctamente'
        ], 200);
    }
}
