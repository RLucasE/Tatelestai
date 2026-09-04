<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory, Searchable,SoftDeletes;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'offer_cates', 'offer_id', 'category_id');
    }

    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_offers')
            ->withPivot(['price', 'quantity']);
    }

    public function fullProducts()
    {
        return $this->belongsToMany(Product::class, 'product_offers')
            ->withPivot(['price', 'quantity', 'expiration_date']);
    }

    public function productOffer()
    {
        return $this->hasMany(ProductOffer::class);
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
            'products' => implode(' ', $this->products->pluck('name')->toArray()),
            'product_descriptions' => implode(' ', $this->products->pluck('description')->toArray()),
            'created_at' => $this->created_at?->timestamp ?? now()->timestamp,
            'state' => $this->state,
            'expiration_datetime' => is_string($this->expiration_datetime)
                ? strtotime($this->expiration_datetime)
                : ($this->expiration_datetime ? $this->expiration_datetime->timestamp : null),
        ];

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
        return $query->with(['products', 'foodEstablishment']);
    }
}
