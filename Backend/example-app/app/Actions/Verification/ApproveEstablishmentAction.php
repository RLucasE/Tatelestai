<?php

namespace App\Actions\Verification;

use App\DTOs\BasicUserDTO;
use App\Enums\UserState;
use App\Events\SellerActivated;
use App\Models\FoodEstablishment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ApproveEstablishmentAction
{
    /**
     * Aprueba la verificación de un establecimiento y activa al usuario vendedor.
     *
     *
     * @throws Exception
     */
    public function execute(
        FoodEstablishment $establishment,
        User $admin,
        ?string $notes = null
    ): FoodEstablishment {
        return DB::transaction(function () use ($establishment, $notes) {
            $lockedEstablishment = FoodEstablishment::where('id', $establishment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEstablishment->verification_status !== 'pending') {
                throw new Exception('El establecimiento ya ha sido verificado o rechazado.', 422);
            }

            $lockedEstablishment->verification_status = 'approved';
            if ($notes) {
                $lockedEstablishment->verification_notes = $notes;
            }
            $lockedEstablishment->save();

            // Activar el usuario vendedor
            $user = $lockedEstablishment->user;
            if ($user && $user->state === UserState::WAITING_FOR_CONFIRMATION->value) {
                $user->state = UserState::ACTIVE;
                $user->save();

                // Notificar por evento tras confirmar la transacción
                DB::afterCommit(function () use ($user) {
                    SellerActivated::dispatch(BasicUserDTO::fromModel($user));
                });
            }

            return $lockedEstablishment->fresh(['user', 'establishmentType', 'trustLogs', 'verificationFiles']);
        });
    }
}
