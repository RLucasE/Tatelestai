<?php

namespace App\DTOs;

use App\Models\User;

class BasicUserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $last_name,
        public readonly string $email,
        /** @var string[] */
        public readonly array $roles,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            last_name: $user->last_name,
            email: $user->email,
            roles: $user->roles ? $user->roles->pluck('name')->toArray() : [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
