<?php

namespace App\Models;

use App\Enums\PaymentStatus;
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
        'payment_status' => PaymentStatus::class,
        'gross_amount' => 'float',
        'commission_percentage' => 'float',
        'commission_amount' => 'float',
        'net_amount' => 'float',
        'refunded_at' => 'datetime',
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

    public function review(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EstablishmentReview::class);
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

    /**
     * Obtiene el monto bruto vendido (calculando desde sellDetails si es legacy).
     */
    public function getGrossAmount(): float
    {
        if ((float) $this->gross_amount > 0) {
            return round((float) $this->gross_amount, 2);
        }

        return $this->calculateGrossFromDetails();
    }

    /**
     * Obtiene la comisión calculada (15%).
     */
    public function getCommissionAmount(): float
    {
        if ((float) $this->commission_amount > 0) {
            return round((float) $this->commission_amount, 2);
        }

        $gross = $this->getGrossAmount();
        $rate = (float) ($this->commission_percentage ?: config('services.mercadopago.commission_percentage', 15.0));

        return round($gross * ($rate / 100), 2);
    }

    /**
     * Obtiene la ganancia neta del comercio (85%).
     */
    public function getNetAmount(): float
    {
        if ((float) $this->net_amount > 0) {
            return round((float) $this->net_amount, 2);
        }

        return round($this->getGrossAmount() - $this->getCommissionAmount(), 2);
    }

    /**
     * Calcula el monto bruto sumando los detalles de la venta.
     */
    public function calculateGrossFromDetails(): float
    {
        $this->loadMissing('sellDetails');

        $total = $this->sellDetails->sum(function ($detail) {
            return (int) $detail->offer_quantity * (float) $detail->pack_price;
        });

        return round((float) $total, 2);
    }
}
