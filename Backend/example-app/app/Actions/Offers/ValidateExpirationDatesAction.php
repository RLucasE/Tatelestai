<?php

namespace App\Actions\Offers;

use App\DTOs\CreateNewOfferDTO;
use App\Exceptions\Offer\InvalidExpirationDateException;
use Carbon\Carbon;

class ValidateExpirationDatesAction
{
    /**
     * Valida que la fecha de expiración de la oferta sea mayor a ahora
     * y que las fechas de expiración de los productos no sean mayores a la de la oferta
     *
     * @throws InvalidExpirationDateException
     */
    public function execute(CreateNewOfferDTO $offerDTO): bool
    {
        $now = Carbon::now()->startOfDay();
        $offerExpirationDate = Carbon::parse($offerDTO->expiration_date);

        // Validar que la fecha de expiración de la oferta sea mayor a ahora
        if ($offerExpirationDate <= $now) {
            throw InvalidExpirationDateException::offerDateTooEarly($offerExpirationDate->format('Y-m-d'));
        }

        // Validar que las fechas de expiración de los productos no sean mayores a la de la oferta
        foreach ($offerDTO->products as $product) {
            $productExpirationDate = Carbon::parse($product->expirationDate)->startOfDay();

            if ($productExpirationDate->startOfDay() < $offerExpirationDate->startOfDay()) {
                throw InvalidExpirationDateException::productDateAfterOfferDate(
                    $product->id,
                    $productExpirationDate->format('Y-m-d'),
                    $offerExpirationDate->format('Y-m-d')
                );
            }
        }

        return true;
    }
}
