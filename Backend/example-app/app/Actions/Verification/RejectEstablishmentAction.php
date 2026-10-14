<?php

namespace App\Actions\Verification;

use App\DTOs\BasicUserDTO;
use App\Enums\UserState;
use App\Events\SellerDenied;
use App\Models\FoodEstablishment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class RejectEstablishmentAction
{
    /**
     * Rechaza la verificación de un establecimiento con un motivo explícito.
     *
     *
     * @throws Exception
     */
    public function execute(
        FoodEstablishment $establishment,
        User $admin,
        string $reason
    ): FoodEstablishment {
        return DB::transaction(function () use ($establishment, $reason) {
            $lockedEstablishment = FoodEstablishment::where('id', $establishment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEstablishment->verification_status !== 'pending') {
                throw new Exception('El establecimiento ya ha sido verificado o rechazado.', 422);
            }

            $lockedEstablishment->verification_status = 'rejected';
            $lockedEstablishment->verification_notes = $reason;
            $lockedEstablishment->save();

            $user = $lockedEstablishment->user;
            if ($user && $user->state === UserState::WAITING_FOR_CONFIRMATION->value) {
                $user->state = UserState::REGISTERING;
                $user->save();

                // Notificar por evento tras confirmar la transacción
                DB::afterCommit(function () use ($user) {
                    SellerDenied::dispatch(BasicUserDTO::fromModel($user));
                });
            }

            return $lockedEstablishment->fresh(['user', 'establishmentType', 'verificationFiles']);
        });
    }
}
