<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductOffer;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory;
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
}
