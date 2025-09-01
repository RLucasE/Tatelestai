<?php

namespace App\Repositories;

use App\Models\User;
use App\Enums\UserState;
use App\DTOs\BasicUserDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Get all users with basic information and roles
     *
     * @return Collection
     */
    public function getAllWithRoles(): Collection
    {
        return User::select('id', 'name', 'last_name', 'email')
            ->with(['roles:name'])
            ->get()
            ->map(function ($user) {
                $user->roles = $user->roles->pluck('name')->toArray();
                return $user;
            });
    }

    /**
     * Find user by ID with roles and state
     *
     * @param int $id
     * @return User
     */
    public function findByIdWithRoles(int $id): User
    {
        $user = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with(['roles:name'])
            ->findOrFail($id);

        $user->roles = $user->roles->pluck('name')->toArray();

        return $user;
    }

    /**
     * Find user by ID (basic find)
     *
     * @param int $id
     * @return User
     */
    public function findById(int $id): BasicUserDTO
    {
        return BasicUserDTO::fromModel(User::findOrFail($id));
    }

    /**
     * Get sellers waiting for confirmation with establishment info
     *
     * @return Collection
     */
    public function getSellersWaitingForConfirmation(): Collection
    {
        return User::select('id', 'name', 'last_name', 'email', 'state')
            ->with([
                'roles:name',
                'foodEstablishment:id,user_id,name,establishment_type_id',
                'foodEstablishment.establishmentType:id,name'
            ])
            ->whereHas('roles', function ($query) {
                $query->where('name', 'seller');
            })
            ->where('state', UserState::WAITING_FOR_CONFIRMATION->value)
            ->get()
            ->map(function ($seller) {
                $seller->roles = $seller->roles->pluck('name')->toArray();
                return $seller;
            });
    }

    /**
     * Get seller with establishment details by ID
     *
     * @param int $id
     * @return User
     */
    public function getSellerWithEstablishment(int $id): User
    {
        $seller = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with([
                'roles:name',
                'foodEstablishment:id,user_id,name,establishment_type_id,address',
                'foodEstablishment.establishmentType:id,name'
            ])
            ->findOrFail($id);

        $seller->roles = $seller->roles->pluck('name')->toArray();

        return $seller;
    }

    /**
     * Update user state
     *
     * @param User $user
     * @param string $state
     * @return bool
     */
    public function updateState(User $user, string $state): bool
    {
        $user->state = $state;
        return $user->save();
    }

    public function changeUserState(BasicUserDTO $user, UserState $state): bool
    {
        try {
            return DB::transaction(function () use ($user, $state) {
                $userDB = User::findOrFail($user->id);
                $userDB->state = $state->value;
                return $userDB->save();
            });
        }catch (\Exception $e) {
            throw $e;
        }
    }
    /**
     * Save user changes
     *
     * @param User $user
     * @return bool
     */
    public function save(User $user): bool
    {
        return $user->save();
    }
}
