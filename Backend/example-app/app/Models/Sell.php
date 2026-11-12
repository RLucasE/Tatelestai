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
        'refunded_amount' => 'float',
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

    public function claim(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PurchaseClaim::class);
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

    public function isRefundedOrPartiallyRefunded(): bool
    {
        $paymentStatus = $this->payment_status instanceof PaymentStatus
            ? $this->payment_status
            : PaymentStatus::tryFrom((string) $this->payment_status);

        return in_array($paymentStatus, [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED], true);
    }

    public function getEffectiveRefundedAmount(): float
    {
        $paymentStatus = $this->payment_status instanceof PaymentStatus
            ? $this->payment_status
            : PaymentStatus::tryFrom((string) $this->payment_status);

        $gross = $this->getGrossAmount();

        if ($paymentStatus === PaymentStatus::REFUNDED) {
            $recorded = (float) $this->refunded_amount;

            return $recorded > 0 ? min($gross, round($recorded, 2)) : $gross;
        }

        if ($paymentStatus === PaymentStatus::PARTIALLY_REFUNDED) {
            return min($gross, max(0.0, round((float) $this->refunded_amount, 2)));
        }

        return 0.0;
    }

    public function getEffectiveGrossAmount(): float
    {
        $state = $this->state instanceof SellState
            ? $this->state
            : SellState::tryFrom((string) $this->state);

        $paymentStatus = $this->payment_status instanceof PaymentStatus
            ? $this->payment_status
            : PaymentStatus::tryFrom((string) $this->payment_status);

        if ($state === SellState::CANCELLED || in_array($paymentStatus, [PaymentStatus::PENDING, PaymentStatus::REJECTED, PaymentStatus::REFUNDED], true)) {
            return 0.0;
        }

        return max(0.0, round($this->getGrossAmount() - $this->getEffectiveRefundedAmount(), 2));
    }

    public function getEffectiveCommissionAmount(): float
    {
        $effectiveGross = $this->getEffectiveGrossAmount();
        if ($effectiveGross <= 0) {
            return 0.0;
        }

        $gross = $this->getGrossAmount();
        if ($gross > 0 && abs($effectiveGross - $gross) < 0.01) {
            return $this->getCommissionAmount();
        }

        $rate = (float) ($this->commission_percentage ?: config('services.mercadopago.commission_percentage', 15.0));

        return round($effectiveGross * ($rate / 100), 2);
    }

    public function getEffectiveNetAmount(): float
    {
        $effectiveGross = $this->getEffectiveGrossAmount();
        if ($effectiveGross <= 0) {
            return 0.0;
        }

        return max(0.0, round($effectiveGross - $this->getEffectiveCommissionAmount(), 2));
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
