<?php

namespace App\Actions\Claims;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Models\PurchaseClaim;
use App\Models\PurchaseClaimEvidence;
use App\Models\Sell;
use App\Models\User;
use App\Notifications\RealtimeOrderNotification;
use App\Policies\PurchaseClaimPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreatePurchaseClaimAction
{
    public function __construct(
        private readonly PurchaseClaimPolicy $policy
    ) {}

    /**
     * Crea un reclamo sobre una compra pagada aplicando bloqueo pesimista,
     * validación de política/anti-abuso y resguardo privado de evidencias opcionales.
     *
     * @param  array<int, UploadedFile>  $evidences
     */
    public function execute(
        Sell $sell,
        User $customer,
        ClaimReason $reason,
        string $description,
        array $evidences = []
    ): PurchaseClaim {
        $storedPaths = [];

        try {
            $claim = DB::transaction(function () use ($sell, $customer, $reason, $description, $evidences, &$storedPaths) {
                $lockedSell = Sell::with(['claim', 'foodEstablishment', 'customer'])
                    ->whereKey($sell->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->policy->assertCanClaim($lockedSell, $customer);

                $claim = PurchaseClaim::create([
                    'sell_id' => $lockedSell->id,
                    'customer_id' => $customer->id,
                    'food_establishment_id' => $lockedSell->sold_by,
                    'reason' => $reason->value,
                    'description' => trim($description),
                    'status' => ClaimStatus::PENDING->value,
                    'was_picked_up_at_claim' => (bool) $lockedSell->is_picked_up,
                ]);

                foreach ($evidences as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
                    $filename = Str::uuid()->toString().'.'.$extension;
                    $directory = "claim-evidences/{$claim->id}";
                    $path = $file->storeAs($directory, $filename, 'private');

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('No se pudo almacenar el archivo de evidencia.');
                    }

                    $storedPaths[] = $path;

                    PurchaseClaimEvidence::create([
                        'purchase_claim_id' => $claim->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName() ?: $filename,
                        'mime_type' => $file->getClientMimeType() ?: $file->getMimeType() ?: 'image/jpeg',
                        'file_size' => (int) ($file->getSize() ?: 0),
                    ]);
                }

                return $claim;
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                try {
                    Storage::disk('private')->delete($path);
                } catch (\Throwable) {
                    // Mejor esfuerzo de limpieza si la transacción falló
                }
            }

            throw $e;
        }

        $claim->loadMissing(['sell.sellDetails', 'sell.foodEstablishment', 'customer', 'evidences']);
        $this->notifyAdmins($claim);

        return $claim;
    }

    private function notifyAdmins(PurchaseClaim $claim): void
    {
        try {
            $admins = User::role('admin')->get();
            if ($admins->isEmpty()) {
                return;
            }

            $establishmentName = $claim->foodEstablishment?->name ?? 'Comercio';
            $reasonLabel = $claim->reason?->label() ?? 'Reclamo';

            foreach ($admins as $admin) {
                $admin->notify(new RealtimeOrderNotification(
                    eventType: 'claim_created',
                    title: 'Nuevo reclamo de compra',
                    message: "Reclamo #{$claim->id} sobre compra #{$claim->sell_id} ({$establishmentName}) — {$reasonLabel}.",
                    sellId: $claim->sell_id,
                    establishmentId: $claim->food_establishment_id,
                    actionUrl: '/adm/claims',
                    meta: [
                        'claim_id' => $claim->id,
                        'reason' => $claim->reason?->value,
                        'was_picked_up_at_claim' => (bool) $claim->was_picked_up_at_claim,
                    ]
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la notificación de nuevo reclamo a administradores.', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
