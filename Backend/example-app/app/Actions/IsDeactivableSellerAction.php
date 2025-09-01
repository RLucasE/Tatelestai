<?php

namespace App\Actions;

use App\Models\User;
use App\Enums\UserState;

/**
 * Determines if a seller can be deactivated
 */
class IsDeactivableSellerAction
{
    /**
     * Check if a seller can be deactivated
     *
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function execute(int $userId): bool
    {
        $user = User::findOrFail($userId);
        if (!$user->hasRole('seller')) {
            throw new \Exception('El usuario no tiene rol seller.');
        }
        if ($user->state === UserState::ACTIVE->value || $user->state === UserState::DENIED_CONFIRMATION->value) {
            return true;
        }
        return false;
    }
}
