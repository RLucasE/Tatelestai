<?php

namespace App\Actions;

use App\Enums\UserState;
use App\Models\User;

/**
 * Determines if a seller can be activated
 */
class IsSellerActivableAction
{
    /**
     * Check if a seller can be activated
     *
     * @throws \Exception
     */
    public function execute(int $userId): bool
    {
        $user = User::findOrFail($userId);

        if (! $user->hasRole('seller')) {
            throw new \Exception('El usuario no tiene rol seller.');
        }

        if ($user->state === UserState::WAITING_FOR_CONFIRMATION->value ||
            $user->state === UserState::INACTIVE->value) {
            return true;
        }

        return false;
    }
}
