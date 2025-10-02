<?php

namespace App\Actions\Offers;

use App\DTOs\CreateNewOfferDTO;
use Carbon\Carbon;

class ValidateExpirationDatesAction
{
    /**
     * Valida que la fecha de expiración de la oferta sea mayor a ahora
     * y que las fechas de expiración de los productos no sean mayores a la de la oferta
     *
     * @param CreateNewOfferDTO $offerDTO
     * @return bool
     * @throws \Exception
     */
    public function execute(CreateNewOfferDTO $offerDTO): bool
    {
        $now = Carbon::now()->startOfDay();
        $offerExpirationDate = Carbon::parse($offerDTO->expiration_date);

        // Validar que la fecha de expiración de la oferta sea mayor a ahora
        if ($offerExpirationDate <= $now) {
            throw new \Exception("La fecha de expiración de la oferta debe ser mayor a la fecha actual");
        }

        // Validar que las fechas de expiración de los productos no sean mayores a la de la oferta
        foreach ($offerDTO->products as $product) {
            $productExpirationDate = Carbon::parse($product->expirationDate)->startOfDay();

            if ($productExpirationDate->startOfDay() < $offerExpirationDate->startOfDay()) {
                throw new \Exception('la fecha de expiración del producto no puede ser anterior a la fecha de la oferta');
            }
        }

        return true;
    }
}
