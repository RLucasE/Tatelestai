<?php

namespace App\Actions\Packs;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Models\PackTemplate;
use App\Models\User;

class ValidatePackTemplateOwnershipAction
{
    public function __construct(private readonly GetUserEstablishmentAction $getUserEstablishmentAction) {}

    /**
     * Valida si una plantilla de pack pertenece al establecimiento del usuario.
     */
    public function execute(PackTemplate $template, ?User $user = null): bool
    {
        $establishment = $this->getUserEstablishmentAction->execute($user);

        if (! $establishment) {
            return false;
        }

        return $template->food_establishment_id === $establishment->id;
    }
}
