<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCart extends Model
{
    protected $guarded = ['id'];


    public function offerCarts()
    {
        return $this->hasMany(OfferCart::class);
    }
}
