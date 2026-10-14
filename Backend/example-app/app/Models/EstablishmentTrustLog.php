<?php

namespace App\Models;

use App\Enums\TrustScoreCriterion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablishmentTrustLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'points' => 'integer',
        'source_type' => TrustScoreCriterion::class,
    ];

    public function foodEstablishment(): BelongsTo
    {
        return $this->belongsTo(FoodEstablishment::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
