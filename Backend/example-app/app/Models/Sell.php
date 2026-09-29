<?php

namespace App\Models;

use App\Enums\SellState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $guarded = ['id'];

    /** @use HasFactory<\Database\Factories\SellFactory> */
    use HasFactory;

    protected $casts = [
        'is_picked_up' => 'boolean',
        'picked_up_at' => 'datetime',
        'max_pickup_datetime' => 'datetime',
        'state' => SellState::class,
    ];

    public function sellDetails()
    {
        return $this->hasMany(SellDetail::class);
    }

    public function foodEstablishment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FoodEstablishment::class, 'sold_by');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'bought_by');
    }

    /**
     * Obtiene la fecha/hora de inicio de la franja de retiro más temprana entre las ofertas del pedido.
     */
    public function getPickupStartDatetime(): ?\Carbon\Carbon
    {
        $this->loadMissing('sellDetails.offer');

        $dates = $this->sellDetails
            ->map(fn ($detail) => $detail->offer?->pickup_start_datetime)
            ->filter();

        if ($dates->isNotEmpty()) {
            return \Carbon\Carbon::parse($dates->min());
        }

        if ($this->max_pickup_datetime) {
            return \Carbon\Carbon::parse($this->max_pickup_datetime)->subHours(2);
        }

        return $this->created_at ? \Carbon\Carbon::parse($this->created_at) : null;
    }
}
