<?php

namespace App\Actions\Sell;

use App\DTOs\PreparePurchaseDTO;
use App\Models\Sell;
use Illuminate\Support\Str;

class GeneratePickupCodeAction
{
    /**
     * Genera un código único de pickup basado en información de la venta.
     *
     * Formato: UUEE-TTHH-XXXX
     * - UU: Últimos 2 dígitos del user_id (comprador)
     * - EE: Últimos 2 dígitos del establishment_id (vendedor)
     * - TT: Timestamp reducido (segundos del día)
     * - HH: Hash de las ofertas
     * - XXXX: Random para garantizar unicidad
     *
     * @param int $userId ID del usuario comprador
     * @param int $establishmentId ID del establecimiento vendedor
     * @param PreparePurchaseDTO $preparePurchaseDTO Datos de la compra
     * @return string Código de pickup único
     */
    public function execute(int $userId, int $establishmentId, PreparePurchaseDTO $preparePurchaseDTO): string
    {
        do {
            // Obtener los últimos 2 dígitos del user_id
            $userPart = str_pad($userId % 100, 2, '0', STR_PAD_LEFT);

            // Obtener los últimos 2 dígitos del establishment_id
            $establishmentPart = str_pad($establishmentId % 100, 2, '0', STR_PAD_LEFT);

            // Generar timestamp reducido (segundos del día convertidos a base 36)
            $secondsOfDay = now()->hour * 3600 + now()->minute * 60 + now()->second;
            $timePart = strtoupper(base_convert($secondsOfDay, 10, 36));

            // Generar hash basado en los IDs de las ofertas
            $offerIds = array_map(fn($offer) => $offer->id, $preparePurchaseDTO->offers);
            $offerHash = strtoupper(substr(md5(implode('-', $offerIds)), 0, 2));

            // Generar parte aleatoria para garantizar unicidad
            $randomPart = strtoupper(Str::random(4));

            // Construir el código final
            $pickupCode = "{$userPart}{$establishmentPart}-{$timePart}{$offerHash}-{$randomPart}";

            // Verificar si el código ya existe en la base de datos
            $exists = Sell::where('pickup_code', $pickupCode)->exists();

        } while ($exists);

        return $pickupCode;
    }
}
