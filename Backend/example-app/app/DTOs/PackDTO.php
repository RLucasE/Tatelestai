<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PackDTO
{
    public function __construct(
        public readonly ?int $packTemplateId = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?array $allergens = null,
        public readonly ?float $estimatedWeightKg = null,
        public readonly ?int $price = null,
        public readonly ?int $minimumValue = null,
        public readonly ?int $quantity = null,
        public readonly ?string $pickupStartDatetime = null,
        public readonly ?string $pickupEndDatetime = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $title = $request->input('title');
        $description = $request->input('description');

        return new self(
            packTemplateId: $request->filled('pack_template_id') ? (int) $request->input('pack_template_id') : null,
            title: $title !== null ? (string) $title : null,
            description: $description !== null ? (string) $description : null,
            allergens: $request->input('allergens'),
            estimatedWeightKg: $request->filled('estimated_weight_kg')
                ? (float) $request->input('estimated_weight_kg')
                : null,
            price: $request->filled('price') ? (int) $request->input('price') : null,
            minimumValue: $request->filled('minimum_value') ? (int) $request->input('minimum_value') : null,
            quantity: $request->filled('quantity') ? (int) $request->input('quantity') : null,
            pickupStartDatetime: $request->filled('pickup_start_datetime')
                ? (string) $request->input('pickup_start_datetime')
                : null,
            pickupEndDatetime: $request->filled('pickup_end_datetime')
                ? (string) $request->input('pickup_end_datetime')
                : null,
        );
    }

    /**
     * Campos informados para persistencia o actualización parcial.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return array_filter([
            'pack_template_id' => $this->packTemplateId,
            'title' => $this->title,
            'description' => $this->description,
            'allergens' => $this->allergens,
            'estimated_weight_kg' => $this->estimatedWeightKg,
            'price' => $this->price,
            'minimum_value' => $this->minimumValue,
            'quantity' => $this->quantity,
            'pickup_start_datetime' => $this->pickupStartDatetime,
            'pickup_end_datetime' => $this->pickupEndDatetime,
        ], fn ($value) => $value !== null);
    }
}
