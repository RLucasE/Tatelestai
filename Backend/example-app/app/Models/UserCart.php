<?php

namespace App\Models;

use App\Enums\CartState;
use Illuminate\Database\Eloquent\Model;

class UserCart extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class);
    }

    public function offerCarts()
    {
        return $this->hasMany(OfferCart::class);
    }

    public function scopeActive($query)
    {
        return $query->where('state', CartState::ACTIVE->value);
    }
}
