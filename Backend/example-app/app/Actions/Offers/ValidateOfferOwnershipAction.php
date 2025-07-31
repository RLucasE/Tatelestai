<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use App\Models\User;

class ValidateOfferOwnershipAction
{

    public function __construct(private GetUserEstablishmentAction $getUserEstablishment) {}

    /**
     * Valida si una oferta pertenece a un usuario
     *
     * @param Offer $offer Oferta a validar
     * @param User $user Usuario a validar
     * @return bool True si la oferta pertenece al usuario, false en caso contrario
     */
    public function execute(Offer $offer, User $user): bool
    {
        $establishment = $this->getUserEstablishment->execute($user);

        if (!$establishment || !$user) {
            return false;
        }

        if ($offer->food_establishment_id !== $establishment->id) {
            return false;
        }

        return true;
    }
}
