<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstablishmentVerificationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_establishment_id',
        'file_path',
        'file_type',
        'document_category',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'document_category' => \App\Enums\TrustScoreCriterion::class,
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'assigned_points',
    ];

    /**
     * Puntos asignados en el log de confianza para este archivo
     */
    public function getAssignedPointsAttribute(): ?int
    {
        if ($this->relationLoaded('foodEstablishment') && $this->foodEstablishment && $this->foodEstablishment->relationLoaded('trustLogs')) {
            $log = $this->foodEstablishment->trustLogs
                ->where('is_active', true)
                ->first(function ($l) {
                    $meta = is_array($l->metadata) ? $l->metadata : (json_decode($l->metadata, true) ?? []);

                    return isset($meta['file_id']) && (int) $meta['file_id'] === (int) $this->id;
                });

            return $log ? (int) $log->points : null;
        }

        $log = EstablishmentTrustLog::where('food_establishment_id', $this->food_establishment_id)
            ->where('is_active', true)
            ->whereJsonContains('metadata->file_id', $this->id)
            ->first();

        return $log ? (int) $log->points : null;
    }

    /**
     * Relación con FoodEstablishment
     */
    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class, 'food_establishment_id');
    }

    /**
     * Administrador que revisó el archivo
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
