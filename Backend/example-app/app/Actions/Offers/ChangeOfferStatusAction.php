<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use App\Enums\OfferState;
use App\Exceptions\Offer\OfferStatusChangeException;

/**
 * Action para cambiar el estado de una oferta
 */
class ChangeOfferStatusAction
{
    /**
     * Ejecuta el cambio de estado de una oferta
     *
     * @param int $offerId ID de la oferta
     * @param string $newStatus Nuevo estado de la oferta
     * @return Offer La oferta actualizada
     * @throws OfferStatusChangeException
     */
    public function execute(int $offerId, string $newStatus): Offer
    {
        // Buscar la oferta
        $offer = Offer::findOrFail($offerId);

        // Verificar que el estado no es el mismo
        if ($offer->state === $newStatus) {
            throw OfferStatusChangeException::sameStatus($offerId, $newStatus);
        }

        // Validar que el nuevo estado es válido usando el enum
        if (!$this->isValidStatus($newStatus)) {
            throw OfferStatusChangeException::invalidStatus($offerId, $offer->state, $newStatus);
        }

        // Actualizar el estado
        $offer->state = $newStatus;
        $offer->save();

        return $offer;
    }

    /**
     * Verifica si el estado es válido según el enum OfferState
     *
     * @param string $status
     * @return bool
     */
    private function isValidStatus(string $status): bool
    {
        $validStates = array_column(OfferState::cases(), 'value');
        return in_array($status, $validStates);
    }

    /**
     * Obtiene todos los estados válidos
     *
     * @return array
     */
    public function getValidStatuses(): array
    {
        return array_column(OfferState::cases(), 'value');
    }
}
