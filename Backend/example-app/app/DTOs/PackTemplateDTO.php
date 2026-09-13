<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PackTemplateDTO
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?array $allergens = null,
        public readonly ?float $estimatedWeightKg = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $title = $request->input('title');
        $description = $request->input('description');

        return new self(
            title: $title !== null ? (string) $title : null,
            description: $description !== null ? (string) $description : null,
            allergens: $request->input('allergens'),
            estimatedWeightKg: $request->filled('estimated_weight_kg')
                ? (float) $request->input('estimated_weight_kg')
                : null,
        );
    }

    /**
     * Campos a persistir o actualizar (solo los informados).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'allergens' => $this->allergens,
            'estimated_weight_kg' => $this->estimatedWeightKg,
        ], fn ($value) => $value !== null);
    }
}
