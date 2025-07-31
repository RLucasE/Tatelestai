<?php

namespace App\Actions\Offers\Seller;

use App\Actions\Offers\CreateOfferAction;
use App\Models\Offer;
use Illuminate\Http\Request;

class CreateSellerOfferAction
{
    private CreateOfferAction $createOfferAction;

    public function __construct(CreateOfferAction $createOfferAction)
    {
        $this->createOfferAction = $createOfferAction;
    }

    /**
     * Crea una nueva oferta para el vendedor
     *
     * @param Request $request Datos de la oferta
     * @return array Resultado con la oferta creada o mensaje de error
     */
    public function execute(Request $request): array
    {
        try {
            $offer = $this->createOfferAction->execute($request);

            return [
                'success' => true,
                'message' => 'Oferta creada exitosamente',
                'offer' => $offer,
                'status' => 201
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Uno o más productos no pertenecen a tu establecimiento',
                'status' => 403
            ];
        }
    }
}