<?php

namespace App\Actions\Offers;

use App\DTOs\PrepareOfferDTO;
use App\Models\Offer;

class ValidateOfferExpirationFromDTOAction
{
    /**
     * Valida si las ofertas están vencidas basándose en un array de PrepareOfferDTO
     *
     * @param PrepareOfferDTO[] $offerDTOs
     * @return bool
     * @throws \Exception
     */
    public function execute(array $offerDTOs): bool
    {
        if (empty($offerDTOs)) {
            return true;
        }

        // Extraer los IDs de las ofertas
        $offerIds = array_map(fn($dto) => $dto->id, $offerDTOs);

        // Consultar las ofertas con sus fechas de expiración
        $offers = Offer::whereIn('id', $offerIds)
            ->select('id', 'expiration_datetime')
            ->get()
            ->keyBy('id');

        // Validar cada oferta
        foreach ($offerDTOs as $offerDTO) {
            $offer = $offers->get($offerDTO->id);

            if (!$offer) {
                throw new \Exception("La oferta con ID {$offerDTO->id} no existe");
            }

            if ($offer->expiration_datetime && $offer->expiration_datetime < now()) {
                throw new \Exception("La oferta '{$offerDTO->title}' está expirada");
            }

            if(!$offer->expiration_datetime) {
                throw new \Exception("La oferta '{$offerDTO->title}' no tiene fecha de expiración");
            }
        }

        return true;
    }
}
