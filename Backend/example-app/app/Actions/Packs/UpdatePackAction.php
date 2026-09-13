<?php

namespace App\Actions\Packs;

use App\DTOs\PackDTO;
use App\Models\Offer;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Actualiza cupos, precio, valor mínimo y/o ventana de retiro de un pack publicado.
 */
class UpdatePackAction
{
    /**
     * @throws InvalidArgumentException Si la ventana de retiro resultante es inválida
     */
    public function execute(Offer $offer, PackDTO $dto): Offer
    {
        $attributes = $dto->toAttributes();

        if (isset($attributes['pickup_start_datetime']) || isset($attributes['pickup_end_datetime'])) {
            $pickupStart = Carbon::parse($attributes['pickup_start_datetime'] ?? $offer->pickup_start_datetime);
            $pickupEnd = Carbon::parse($attributes['pickup_end_datetime'] ?? $offer->expiration_datetime);

            if ($pickupStart->greaterThanOrEqualTo($pickupEnd)) {
                throw new InvalidArgumentException('La hora de inicio de retiro debe ser anterior a la de fin');
            }

            $attributes['pickup_start_datetime'] = $pickupStart->toDateTimeString();
            $attributes['expiration_datetime'] = $pickupEnd->toDateTimeString();
        }

        unset($attributes['pickup_end_datetime']);

        $offer->update($attributes);

        return $offer->refresh();
    }
}
