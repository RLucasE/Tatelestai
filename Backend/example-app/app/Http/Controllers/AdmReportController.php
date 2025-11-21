<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}

