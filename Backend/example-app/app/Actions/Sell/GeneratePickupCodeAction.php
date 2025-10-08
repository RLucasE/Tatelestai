<?php

namespace App\Actions\Sell;

use App\DTOs\PreparePurchaseDTO;
use App\Models\Sell;
use Illuminate\Support\Str;

class GeneratePickupCodeAction
{
    /**
     * Genera un código único de pickup completamente aleatorio.
     *
     * Formato: XXXX-XXXX-XXXX (12 caracteres alfanuméricos)
     * - El código es completamente aleatorio y no revela información sensible
     * - Usa caracteres seguros (sin ambigüedades: sin 0, O, I, 1, etc.)
     * - Garantiza unicidad verificando contra la base de datos
     *
     * @param int $userId ID del usuario comprador (no usado en el código)
     * @param int $establishmentId ID del establecimiento vendedor (no usado en el código)
     * @param PreparePurchaseDTO $preparePurchaseDTO Datos de la compra (no usado en el código)
     * @return string Código de pickup único
     */
    public function execute(int $userId, int $establishmentId, PreparePurchaseDTO $preparePurchaseDTO): string
    {
        $safeChars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $segment1 = $this->generateRandomSegment(4, $safeChars);
            $segment2 = $this->generateRandomSegment(4, $safeChars);
            $segment3 = $this->generateRandomSegment(4, $safeChars);

            $pickupCode = "{$segment1}-{$segment2}-{$segment3}";

            $exists = Sell::where('pickup_code', $pickupCode)->exists();

        } while ($exists);

        return $pickupCode;
    }

    /**
     * Genera un segmento aleatorio de caracteres seguros
     *
     * @param int $length Longitud del segmento
     * @param string $characters Caracteres permitidos
     * @return string Segmento aleatorio
     */
    private function generateRandomSegment(int $length, string $characters): string
    {
        $segment = '';
        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $segment .= $characters[random_int(0, $maxIndex)];
        }

        return $segment;
    }
}
