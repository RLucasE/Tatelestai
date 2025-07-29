<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferCart extends Model
{
    protected $guarded = ["id"];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
