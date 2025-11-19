<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reason' => ReportReason::class,
        'status' => ReportStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relación polimórfica con Offer, FoodEstablishment o User
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que creó el reporte
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Administrador que revisó el reporte
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope para filtrar reportes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', ReportStatus::PENDING);
    }

    /**
     * Scope para filtrar reportes en revisión
     */
    public function scopeReviewing($query)
    {
        return $query->where('status', ReportStatus::REVIEWING);
    }

    /**
     * Scope para filtrar reportes resueltos
     */
    public function scopeResolved($query)
    {
        return $query->where('status', ReportStatus::RESOLVED);
    }

    /**
     * Scope para filtrar reportes descartados
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', ReportStatus::DISMISSED);
    }

    /**
     * Scope para filtrar por tipo de entidad reportada
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('reportable_type', $type);
    }
}

