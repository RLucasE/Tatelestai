<?php

namespace App\Actions\Packs;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\DTOs\PackDTO;
use App\Enums\OfferState;
use App\Exceptions\Pack\PackTemplateOwnershipException;
use App\Models\Offer;
use App\Models\PackTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Publica una oferta diaria de pack sorpresa a partir de una plantilla existente
 * o creando automáticamente una nueva plantilla si el comerciante crea la oferta desde cero.
 */
class PublishPackAction
{
    public function __construct(
        private readonly GetUserEstablishmentAction $getUserEstablishmentAction
    ) {}

    /**
     * @throws PackTemplateOwnershipException
     * @throws \Exception
     */
    public function execute(PackDTO $dto): Offer
    {
        return DB::transaction(function () use ($dto) {
            $establishment = $this->getUserEstablishmentAction->execute();

            if (! $establishment) {
                throw new \Exception('No se encontró un establecimiento asociado al usuario', 404);
            }

            if ($dto->packTemplateId) {
                $template = PackTemplate::find($dto->packTemplateId);

                if (! $template) {
                    throw new \Exception('La plantilla de pack seleccionada no existe', 404);
                }

                if ($template->food_establishment_id !== $establishment->id) {
                    throw (new PackTemplateOwnershipException)
                        ->setContext($template->id, $establishment->id);
                }
            } else {
                // Crear automáticamente la plantilla a partir de los datos de la oferta
                $template = PackTemplate::create([
                    'food_establishment_id' => $establishment->id,
                    'title' => (string) $dto->title,
                    'description' => (string) $dto->description,
                    'allergens' => $dto->allergens,
                    'estimated_weight_kg' => $dto->estimatedWeightKg,
                ]);
            }

            $pickupStart = Carbon::parse($dto->pickupStartDatetime);
            $pickupEnd = Carbon::parse($dto->pickupEndDatetime);

            return Offer::create([
                'pack_template_id' => $template->id,
                'food_establishment_id' => $establishment->id,
                'title' => $dto->title ?? $template->title,
                'description' => $dto->description ?? $template->description,
                'price' => $dto->price,
                'minimum_value' => $dto->minimumValue,
                'allergens' => $dto->allergens ?? $template->allergens,
                'estimated_weight_kg' => $dto->estimatedWeightKg ?? $template->estimated_weight_kg,
                'quantity' => $dto->quantity,
                'expiration_datetime' => $pickupEnd->toDateTimeString(),
                'pickup_start_datetime' => $pickupStart->toDateTimeString(),
                'state' => OfferState::ACTIVE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
