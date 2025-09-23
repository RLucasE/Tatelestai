<?php

namespace App\Actions;

use App\DTOs\BasicUserDTO;
use App\Enums\UserState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action to change user state
 */
class ChangeUserStateAction
{
    /**
     * Change user state
     *
     * @param BasicUserDTO $user
     * @param UserState $userState
     * @return bool
     * @throws \Exception
     */
    public function execute(BasicUserDTO $user, UserState $userState): bool
    {
        try {
            return DB::transaction(function () use ($user, $userState) {
                $userDB = User::findOrFail($user->id);
                $userDB->state = $userState->value;
                return $userDB->save();
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
