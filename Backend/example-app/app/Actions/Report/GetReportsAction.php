<?php

namespace App\Actions\Report;

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;

class GetReportsAction
{
    /**
     * Obtiene reportes con filtros opcionales
     *
     * @param array $filters Filtros opcionales: status, reportable_type, reported_by, reviewed_by
     * @param bool $withRelations Si debe cargar las relaciones
     * @return Collection Colección de reportes
     */
    public function execute(array $filters = [], bool $withRelations = true): Collection
    {
        $query = Report::query();

        // Cargar relaciones si se solicita
        if ($withRelations) {
            $query->with(['reportable', 'reporter', 'reviewer']);
        }

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por tipo de entidad reportada
        if (isset($filters['reportable_type'])) {
            $query->where('reportable_type', $filters['reportable_type']);
        }

        // Filtro por usuario que reportó
        if (isset($filters['reported_by'])) {
            $query->where('reported_by', $filters['reported_by']);
        }

        // Filtro por revisor
        if (isset($filters['reviewed_by'])) {
            $query->where('reviewed_by', $filters['reviewed_by']);
        }

        // Ordenar por más recientes primero
        $query->orderBy('created_at', 'desc');

        return $query->get();
    }
}

