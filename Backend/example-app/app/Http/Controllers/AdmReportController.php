<?php

namespace App\Http\Controllers;

use App\Actions\DeactivateEstablishmentOffersAction;
use App\Actions\Offers\ChangeOfferStatusAction;
use App\Enums\OfferState;
use App\Enums\ReportStatus;
use App\Enums\UserState;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdmReportController extends Controller
{
    /**
     * Listar reportes hechos por usuarios con sus detalles para revisión administrativa.
     * Soporta filtros opcionales: status y reportable_type.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::with([
            'reportable',
            'reporter:id,name,email',
            'reviewer:id,name,email'
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('reportable_type')) {
            $query->where('reportable_type', $request->string('reportable_type'));
        }

        $reports = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($reports);
    }

    /**
     * Cambiar el estado de un reporte (pending, reviewing, resolved, dismissed)
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(ReportStatus::values())],
            'admin_notes' => ['nullable', 'string', 'max:1000']
        ]);

        $report = Report::findOrFail($id);
        $report->status = ReportStatus::from($validated['status']);
        $report->reviewed_by = $request->user()->id; // administrador autenticado
        $report->reviewed_at = now();
        if (array_key_exists('admin_notes', $validated)) {
            $report->admin_notes = $validated['admin_notes'];
        }
        $report->save();

        return response()->json([
            'message' => 'Report status updated successfully',
            'report' => $report->load(['reportable', 'reporter:id,name,email', 'reviewer:id,name,email'])
        ]);
    }

    /**
     * Tomar medidas sobre el elemento reportado (desactivar seller u oferta)
     */
    public function takeAction(int $id, Request $request): JsonResponse
    {
        $report = Report::with('reportable')->findOrFail($id);

        try {
            DB::beginTransaction();

            $result = [];
            $reportableType = $report->reportable_type;

            // Si es un FoodEstablishment, desactivar al seller
            if ($reportableType === 'App\\Models\\FoodEstablishment') {
                $establishment = FoodEstablishment::findOrFail($report->reportable_id);
                $user = User::findOrFail($establishment->user_id);

                // Desactivar el usuario
                $user->state = UserState::INACTIVE;
                $user->save();

                // Desactivar todas las ofertas del establecimiento
                $deactivateOffersAction = new DeactivateEstablishmentOffersAction();
                $offersDeactivated = $deactivateOffersAction->execute($establishment->id);

                $result = [
                    'action' => 'seller_deactivated',
                    'user_id' => $user->id,
                    'establishment_id' => $establishment->id,
                    'offers_deactivated' => $offersDeactivated
                ];
            }
            // Si es una Offer, desactivarla
            elseif ($reportableType === 'App\\Models\\Offer') {
                $offer = Offer::findOrFail($report->reportable_id);

                $changeOfferStatusAction = new ChangeOfferStatusAction();
                $updatedOffer = $changeOfferStatusAction->execute($offer->id, OfferState::INACTIVE->value);

                $result = [
                    'action' => 'offer_deactivated',
                    'offer_id' => $updatedOffer->id,
                    'previous_state' => $offer->state,
                    'new_state' => $updatedOffer->state
                ];
            }
            // Si es un User (reportado directamente)
            elseif ($reportableType === 'App\\Models\\User') {
                $user = User::findOrFail($report->reportable_id);

                // Desactivar el usuario
                $user->state = UserState::INACTIVE;
                $user->save();

                // Si tiene establecimiento, desactivar sus ofertas
                $offersDeactivated = 0;
                if ($user->foodEstablishment) {
                    $deactivateOffersAction = new DeactivateEstablishmentOffersAction();
                    $offersDeactivated = $deactivateOffersAction->executeByUserId($user->id);
                }

                $result = [
                    'action' => 'user_deactivated',
                    'user_id' => $user->id,
                    'offers_deactivated' => $offersDeactivated
                ];
            }

            // Actualizar el reporte a "resolved"
            $report->status = ReportStatus::RESOLVED;
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now();
            $report->admin_notes = ($report->admin_notes ?? '') . "\n\nAcción tomada: " . ($result['action'] ?? 'unknown');
            $report->save();

            DB::commit();

            return response()->json([
                'message' => 'Action taken successfully',
                'result' => $result,
                'report' => $report->load(['reportable', 'reporter:id,name,email', 'reviewer:id,name,email'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error taking action on report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

