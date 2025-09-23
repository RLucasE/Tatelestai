<?php

namespace App\Actions;

use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\FoodEstablishment;
use Illuminate\Support\Facades\DB;

/**
 * Action to deactivate all offers from a food establishment
 */
class DeactivateEstablishmentOffersAction
{
    /**
     * Deactivate all active offers from a food establishment
     *
     * @param int $foodEstablishmentId
     * @return int Number of offers deactivated
     * @throws \Exception
     */
    public function execute(int $foodEstablishmentId): int
    {
        try {
            return DB::transaction(function () use ($foodEstablishmentId) {
                return Offer::where('food_establishment_id', $foodEstablishmentId)
                    ->where('state', OfferState::ACTIVE->value)
                    ->update(['state' => OfferState::INACTIVE->value]);
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deactivate all active offers from a user's food establishment
     *
     * @param int $userId
     * @return int Number of offers deactivated
     * @throws \Exception
     */
    public function executeByUserId(int $userId): int
    {
        try {
            return DB::transaction(function () use ($userId) {
                $foodEstablishment = FoodEstablishment::where('user_id', $userId)->first();

                if (!$foodEstablishment) {
                    return 0;
                }

                return Offer::where('food_establishment_id', $foodEstablishment->id)
                    ->where('state', OfferState::ACTIVE->value)
                    ->update(['state' => OfferState::INACTIVE->value]);
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
