<?php

namespace App\Actions;

use App\DTOs\BasicUserDTO;
use App\Enums\UserState;
use App\Enums\OfferState;
use App\Models\User;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Action to deactivate a seller and all their offers
 */
class DeactivateSellerAndOffersAction
{
    /**
     * Deactivate seller and all their active offers
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
                // Update user state
                $userDB = User::findOrFail($user->id);
                $userDB->state = $userState->value;
                $userDB->save();
                $foodEstablishment = $userDB->foodEstablishment;

                // Deactivate all active offers from this seller
                $offers = Offer::where('food_establishment_id', $foodEstablishment->id)
                    ->where('state', OfferState::ACTIVE->value)
                    ->update(['state' => OfferState::INACTIVE->value]);


                return true;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
