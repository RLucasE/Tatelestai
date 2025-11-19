<?php

namespace App\Actions\Report;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Exceptions\Report\DuplicateReportException;
use App\Exceptions\Report\ReportableNotFoundException;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateReportAction
{
    /**
     * Crea un nuevo reporte
     *
     * @param string $reportableType Tipo de entidad (Offer, FoodEstablishment, User)
     * @param int $reportableId ID de la entidad a reportar
     * @param int $userId ID del usuario que reporta
     * @param ReportReason $reason Razón del reporte
     * @param string $description Descripción del reporte
     * @return Report Reporte creado
     * @throws ReportableNotFoundException Si la entidad no existe
     * @throws DuplicateReportException Si el usuario ya reportó esta entidad
     */
    public function execute(
        string $reportableType,
        int $reportableId,
        int $userId,
        ReportReason $reason,
        string $description
    ): Report {
        // Validar que la entidad exista
        $this->validateReportableExists($reportableType, $reportableId);

        // Validar que no sea un reporte duplicado
        $this->validateNoDuplicateReport($reportableType, $reportableId, $userId);

        return DB::transaction(function () use ($reportableType, $reportableId, $userId, $reason, $description) {
            return Report::create([
                'reportable_type' => $reportableType,
                'reportable_id' => $reportableId,
                'reported_by' => $userId,
                'reason' => $reason->value,
                'status' => ReportStatus::PENDING->value,
                'description' => $description,
            ]);
        });
    }

    /**
     * Valida que la entidad a reportar exista
     *
     * @param string $reportableType
     * @param int $reportableId
     * @throws ReportableNotFoundException
     */
    private function validateReportableExists(string $reportableType, int $reportableId): void
    {
        $exists = match ($reportableType) {
            Offer::class => Offer::where('id', $reportableId)->exists(),
            FoodEstablishment::class => FoodEstablishment::where('id', $reportableId)->exists(),
            User::class => User::where('id', $reportableId)->exists(),
            default => false,
        };

        if (!$exists) {
            throw new ReportableNotFoundException(
                "The element to report does not exist",
                404,
                $reportableType,
                $reportableId
            );
        }
    }

    /**
     * Valida que el usuario no haya reportado previamente esta entidad
     *
     * @param string $reportableType
     * @param int $reportableId
     * @param int $userId
     * @throws DuplicateReportException
     */
    private function validateNoDuplicateReport(string $reportableType, int $reportableId, int $userId): void
    {
        $existingReport = Report::where('reportable_type', $reportableType)
            ->where('reportable_id', $reportableId)
            ->where('reported_by', $userId)
            ->whereIn('status', [
                ReportStatus::PENDING->value,
                ReportStatus::REVIEWING->value,
            ])
            ->exists();

        if ($existingReport) {
            throw new DuplicateReportException(
                "You have already reported this element",
                409,
                $reportableType,
                $reportableId,
                $userId
            );
        }
    }
}

