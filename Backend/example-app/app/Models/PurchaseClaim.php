<?php

namespace App\Models;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseClaim extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reason' => ClaimReason::class,
        'status' => ClaimStatus::class,
        'was_picked_up_at_claim' => 'boolean',
        'refund_amount' => 'float',
        'refunded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'is_abusive' => 'boolean',
    ];

    public function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function foodEstablishment(): BelongsTo
    {
        return $this->belongsTo(FoodEstablishment::class, 'food_establishment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(PurchaseClaimEvidence::class, 'purchase_claim_id');
    }
}
