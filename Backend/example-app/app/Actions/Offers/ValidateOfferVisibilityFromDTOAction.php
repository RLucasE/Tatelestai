<?php

namespace App\Actions\Offers;

use App\DTOs\PrepareOfferDTO;
use App\Models\Offer;

class ValidateOfferVisibilityFromDTOAction
{
    /**
     * Valida si las ofertas están disponibles para compra según su horario de visibilidad
     * basándose en un array de PrepareOfferDTO.
     *
     * @param  PrepareOfferDTO[]  $offerDTOs
     *
     * @throws \Exception
     */
    public function execute(array $offerDTOs): bool
    {
        if (empty($offerDTOs)) {
            return true;
        }

        $offerIds = array_map(fn ($dto) => $dto->id, $offerDTOs);

        $offers = Offer::whereIn('id', $offerIds)
            ->select('id', 'visible_from', 'title')
            ->get()
            ->keyBy('id');

        foreach ($offerDTOs as $offerDTO) {
            $offer = $offers->get($offerDTO->id);

            if (! $offer) {
                throw new \Exception("La oferta con ID {$offerDTO->id} no existe");
            }

            if ($offer->visible_from && $offer->visible_from->isFuture()) {
                throw new \Exception("La oferta '{$offer->title}' aún no está disponible para compra");
            }
        }

        return true;
    }
}
