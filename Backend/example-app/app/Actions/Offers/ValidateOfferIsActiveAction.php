<?php

namespace App\Actions\Offers;

use App\DTOs\PrepareOfferDTO;
use App\Enums\OfferState;
use App\Models\Offer;

class ValidateOfferIsActiveAction
{
    /**
     * Valida si las ofertas están activas basándose en un array de PrepareOfferDTO
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

        // Consultar las ofertas con sus estados
        $offers = Offer::whereIn('id', $offerIds)
            ->select('id', 'state', 'title')
            ->get()
            ->keyBy('id');

        // Validar cada oferta
        foreach ($offerDTOs as $offerDTO) {
            $offer = $offers->get($offerDTO->id);

            if (!$offer) {
                throw new \Exception("La oferta con ID {$offerDTO->id} no existe");
            }

            if ($offer->state !== OfferState::ACTIVE->value) {
                throw new \Exception("La oferta '{$offer->title}' no está activa (Estado actual: {$offer->state})");
            }
        }

        return true;
    }
}
