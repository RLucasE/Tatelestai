<?php

namespace App\Actions\Packs;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\DTOs\PackTemplateDTO;
use App\Models\PackTemplate;

class CreatePackTemplateAction
{
    public function __construct(
        private readonly GetUserEstablishmentAction $getUserEstablishmentAction
    ) {}

    /**
     * Crea una plantilla de pack sorpresa para el establecimiento del usuario autenticado.
     *
     * @throws \Exception Si el usuario no tiene establecimiento asociado
     */
    public function execute(PackTemplateDTO $dto): PackTemplate
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (! $establishment) {
            throw new \Exception('No se encontró un establecimiento asociado al usuario', 404);
        }

        return PackTemplate::create([
            'food_establishment_id' => $establishment->id,
            'title' => $dto->title,
            'description' => $dto->description,
            'allergens' => $dto->allergens,
            'estimated_weight_kg' => $dto->estimatedWeightKg,
        ]);
    }
}
