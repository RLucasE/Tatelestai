<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'allergens' => 'array',
        'estimated_weight_kg' => 'decimal:2',
        'pickup_start_datetime' => 'datetime',
        'expiration_datetime' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class);
    }

    public function packTemplate()
    {
        return $this->belongsTo(PackTemplate::class);
    }

    public function offerCarts()
    {
        return $this->hasMany(OfferCart::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function toSearchableArray(): array
    {
        $array = [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'food_establishment' => $this->foodEstablishment?->name ?? '',
            'created_at' => $this->created_at?->timestamp ?? now()->timestamp,
            'state' => $this->state,
            'expiration_datetime' => $this->expiration_datetime?->timestamp,
            'price' => (int) $this->price,
            'minimum_value' => (int) $this->minimum_value,
        ];

        if (! empty($this->allergens)) {
            $array['allergens'] = array_values($this->allergens);
        }

        if ($this->pickup_start_datetime) {
            $array['pickup_start_datetime'] = $this->pickup_start_datetime->timestamp;
        }

        if ($this->foodEstablishment?->latitude !== null && $this->foodEstablishment?->longitude !== null) {
            $array['_geoloc'] = [
                (float) $this->foodEstablishment->latitude,
                (float) $this->foodEstablishment->longitude,
            ];
        }

        return $array;
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with('foodEstablishment');
    }
}
