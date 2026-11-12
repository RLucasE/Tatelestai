<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseClaimEvidence extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'purchase_claim_evidences';

    protected $guarded = ['id'];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(PurchaseClaim::class, 'purchase_claim_id');
    }
}
