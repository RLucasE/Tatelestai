<?php

namespace App\DTOs;

use App\Models\EstablishmentType;
use Illuminate\Http\Request;

class EstablishmentTypeDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $description,
    )
    {
    }

    public function fromRequest(Request $request): self
    {
        return new self(
            id: $request->get('id'),
            name: $request->get('name'),
            slug: $request->get('slug'),
            description: $request->get('description'),
        );
    }

    public static function fromModel(EstablishmentType $establishmentType): self
    {
        return new self(
            id: $establishmentType->id,
            name: $establishmentType->name,
            slug: $establishmentType->slug,
            description: $establishmentType->description,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
