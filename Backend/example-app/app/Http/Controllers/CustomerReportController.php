<?php

namespace App\Http\Controllers;

use App\Actions\Report\CreateReportAction;
use App\Actions\Report\GetReportsAction;
use App\Enums\ReportReason;
use App\Exceptions\Report\DuplicateReportException;
use App\Exceptions\Report\ReportableNotFoundException;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerReportController extends Controller
{
    public function __construct(
        private readonly CreateReportAction $createReportAction,
        private readonly GetReportsAction $getReportsAction
    ) {}

    /**
     * Crear un nuevo reporte (Offer o FoodEstablishment)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validar fuera del try-catch para que Laravel maneje los errores de validación correctamente (422)
        $validated = $request->validate([
            'reportable_type' => ['required', 'string', Rule::in(['offer', 'establishment'])],
            'reportable_id' => 'required|integer|min:1',
            'reason' => ['required', 'string', Rule::in(ReportReason::values())],
            'description' => 'required|string|min:10|max:1000',
        ]);

        try {
            // Mapear tipo simple a clase completa
            $reportableTypeMap = [
                'offer' => Offer::class,
                'establishment' => FoodEstablishment::class,
            ];

            $reportableType = $reportableTypeMap[$validated['reportable_type']];

            // Validar que la entidad exista antes de crear el reporte
            $exists = match ($reportableType) {
                Offer::class => Offer::where('id', $validated['reportable_id'])->exists(),
                FoodEstablishment::class => FoodEstablishment::where('id', $validated['reportable_id'])->exists(),
                default => false,
            };

            if (!$exists) {
                return response()->json([
                    'error' => 'The element you are trying to report does not exist'
                ], 404);
            }

            $report = $this->createReportAction->execute(
                reportableType: $reportableType,
                reportableId: $validated['reportable_id'],
                userId: Auth::id(),
                reason: ReportReason::from($validated['reason']),
                description: $validated['description']
            );

            return response()->json([
                'message' => 'Report created successfully',
                'data' => [
                    'id' => $report->id,
                    'reportable_type' => $validated['reportable_type'],
                    'reportable_id' => $report->reportable_id,
                    'reason' => $report->reason->label(),
                    'status' => $report->status->label(),
                    'created_at' => $report->created_at,
                ]
            ], 201);

        } catch (DuplicateReportException $e) {
            return response()->json([
                'error' => 'You have already reported this element'
            ], 409);

        } catch (ReportableNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while creating the report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los reportes del usuario autenticado
     *
     * @return JsonResponse
     */
    public function myReports(): JsonResponse
    {
        try {
            $reports = $this->getReportsAction->execute([
                'reported_by' => Auth::id()
            ]);

            $formattedReports = $reports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'reportable_type' => class_basename($report->reportable_type),
                    'reportable_id' => $report->reportable_id,
                    'reportable_name' => $this->getReportableName($report),
                    'reason' => $report->reason->label(),
                    'status' => $report->status->label(),
                    'description' => $report->description,
                    'admin_notes' => $report->admin_notes,
                    'created_at' => $report->created_at,
                    'reviewed_at' => $report->reviewed_at,
                ];
            });

            return response()->json([
                'data' => $formattedReports
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching reports',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el nombre de la entidad reportada
     *
     * @param $report
     * @return string
     */
    private function getReportableName($report): string
    {
        if (!$report->reportable) {
            return 'N/A';
        }

        return match ($report->reportable_type) {
            Offer::class => $report->reportable->title ?? 'Unknown Offer',
            FoodEstablishment::class => $report->reportable->name ?? 'Unknown Establishment',
            default => 'Unknown',
        };
    }
}

