<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductOffer;
use Laravel\Scout\Searchable;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory, SoftDeletes,Searchable;
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

    public function productOffer()
    {
        return $this->hasMany(ProductOffer::class);
    }
    public function offerCarts()
    {
        return $this->hasMany(OfferCart::class);
    }

    public function toSearchableArray(){
        return [
            'id' => (string)$this->id,
            'title' => $this->title,
            'description' => $this->description,
            'food_establishment' => $this->foodEstablishment->name,
            'products' => implode(' ', $this->products->pluck('name')->toArray()),
            'created_at' => $this->created_at->timestamp,
            'state' => $this->state,
            'expiration_datetime' => is_string($this->expiration_datetime)
                ? strtotime($this->expiration_datetime)
                : ($this->expiration_datetime ? $this->expiration_datetime->timestamp : null)
        ];
    }
}
