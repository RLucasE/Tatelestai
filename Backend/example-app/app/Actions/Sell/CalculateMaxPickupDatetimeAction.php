<?php

namespace App\Actions\Sell;

use App\DTOs\PrepareOfferDTO;
use App\Models\Offer;
use Carbon\Carbon;
use InvalidArgumentException;

class CalculateMaxPickupDatetimeAction
{
    /**
     * Calcula la fecha/hora máxima para retirar el pedido basada en múltiples ofertas.
     * Toma las fechas de expiración de las ofertas y devuelve la más cercana a ahora (la más temprana).
     *
     * @param array<int, PrepareOfferDTO> $offers
     * @return Carbon
     * @throws InvalidArgumentException
     */
    public function execute(array $offers): Carbon
    {
        if (empty($offers)) {
            throw new InvalidArgumentException('No se recibieron ofertas para calcular max_pickup_datetime');
        }

        $dates = [];

        foreach ($offers as $offer) {
            // Obtener el ID de la oferta según el tipo
            $offerId = null;

            if ($offer instanceof PrepareOfferDTO) {
                $offerId = $offer->id;
            } elseif (is_array($offer) && isset($offer['id'])) {
                $offerId = $offer['id'];
            }

            if (!$offerId) {
                continue;
            }

            $offerModel = Offer::find($offerId);

            if ($offerModel && !empty($offerModel->expiration_datetime)) {
                $this->pushParsedDate($dates, $offerModel->expiration_datetime);
            }
        }

        if (empty($dates)) {
            throw new InvalidArgumentException('No se encontraron fechas de expiración válidas en las ofertas proporcionadas');
        }

        // Ordenar fechas de menor a mayor y devolver la más cercana (primera)
        usort($dates, fn(Carbon $a, Carbon $b) => $a->lessThan($b) ? -1 : 1);

        return $dates[0];
    }

    /**
     * Intenta parsear una fecha y agregarla al array si es válida
     *
     * @param array $dates
     * @param string|Carbon $date
     * @return void
     */
    private function pushParsedDate(array &$dates, string|Carbon $date): void
    {
        try {
            if ($date instanceof Carbon) {
                $dates[] = $date;
            } else {
                $parsedDate = Carbon::parse($date);
                $dates[] = $parsedDate;
            }
        } catch (\Throwable $e) {
            // Ignorar fechas inválidas silenciosamente
        }
    }
}

