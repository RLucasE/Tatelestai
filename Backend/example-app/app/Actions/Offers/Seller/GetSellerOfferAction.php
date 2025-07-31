<?php

namespace App\Actions\Offers\Seller;

use App\Actions\Offers\GetOfferAction;
use App\Actions\Offers\ValidateOfferOwnershipAction;
use App\Models\Offer;
use App\Models\User;

class GetSellerOfferAction
{
    private GetOfferAction $getOfferAction;
    private ValidateOfferOwnershipAction $validateOfferOwnershipAction;

    public function __construct(
        GetOfferAction $getOfferAction,
        ValidateOfferOwnershipAction $validateOfferOwnershipAction
    ) {
        $this->getOfferAction = $getOfferAction;
        $this->validateOfferOwnershipAction = $validateOfferOwnershipAction;
    }

    /**
     * Obtiene una oferta específica del vendedor
     *
     * @param int $offerId ID de la oferta
     * @param User $user Usuario vendedor
     * @return array Resultado con la oferta o mensaje de error
     */
    public function execute(int $offerId, User $user): array
    {
        $offer = $this->getOfferAction->execute($offerId);

        if (!$offer) {
            return [
                'success' => false,
                'message' => 'Oferta no encontrada',
                'status' => 404
            ];
        }

        if (!$this->validateOfferOwnershipAction->execute($offer, $user)) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para acceder a esta oferta',
                'status' => 403
            ];
        }

        $fullOffer = $this->getOfferAction->execute($offerId, true);

        return [
            'success' => true,
            'data' => $fullOffer,
            'status' => 200
        ];
    }
}