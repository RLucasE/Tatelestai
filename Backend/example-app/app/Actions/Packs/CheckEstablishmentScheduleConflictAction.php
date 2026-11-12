<?php

namespace App\Actions\Packs;

use App\Enums\OfferState;
use App\Models\Offer;
use Carbon\Carbon;

/**
 * Verifica si un establecimiento tiene otras ofertas activas vigentes con un horario
 * de retiro distinto al de la oferta recién publicada o actualizada.
 */
class CheckEstablishmentScheduleConflictAction
{
    /**
     * @return array{
     *     has_conflict: bool,
     *     existing_schedules: array<int, array{
     *         pickup_start_datetime: string,
     *         expiration_datetime: string,
     *         offers_count: int
     *     }>,
     *     warning_message: ?string
     * }
     */
    public function execute(
        int $foodEstablishmentId,
        string|Carbon $pickupStartDatetime,
        string|Carbon $expirationDatetime,
        ?int $excludeOfferId = null
    ): array {
        $targetStart = Carbon::parse($pickupStartDatetime)->format('Y-m-d H:i');
        $targetEnd = Carbon::parse($expirationDatetime)->format('Y-m-d H:i');
        $targetKey = "{$targetStart}|{$targetEnd}";

        $query = Offer::query()
            ->where('food_establishment_id', $foodEstablishmentId)
            ->where('state', OfferState::ACTIVE->value)
            ->where('quantity', '>', 0)
            ->where('expiration_datetime', '>', now());

        if ($excludeOfferId !== null) {
            $query->where('id', '!=', $excludeOfferId);
        }

        $otherActiveOffers = $query->get();

        $conflictingSchedules = [];

        foreach ($otherActiveOffers as $offer) {
            if (! $offer->pickup_start_datetime || ! $offer->expiration_datetime) {
                continue;
            }

            $startFormatted = Carbon::parse($offer->pickup_start_datetime)->format('Y-m-d H:i');
            $endFormatted = Carbon::parse($offer->expiration_datetime)->format('Y-m-d H:i');
            $scheduleKey = "{$startFormatted}|{$endFormatted}";

            if ($scheduleKey === $targetKey) {
                continue;
            }

            if (! isset($conflictingSchedules[$scheduleKey])) {
                $conflictingSchedules[$scheduleKey] = [
                    'pickup_start_datetime' => Carbon::parse($offer->pickup_start_datetime)->toDateTimeString(),
                    'expiration_datetime' => Carbon::parse($offer->expiration_datetime)->toDateTimeString(),
                    'offers_count' => 0,
                ];
            }

            $conflictingSchedules[$scheduleKey]['offers_count']++;
        }

        $existingSchedules = array_values($conflictingSchedules);
        $hasConflict = count($existingSchedules) > 0;

        return [
            'has_conflict' => $hasConflict,
            'existing_schedules' => $existingSchedules,
            'warning_message' => $hasConflict
                ? 'Tu comercio tiene otras ofertas activas en diferentes horarios de retiro. Los clientes verán y comprarán cada turno por separado.'
                : null,
        ];
    }
}
