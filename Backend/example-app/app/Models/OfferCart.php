<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferCart extends Model
{
    protected $guarded = ["id"];
    use softDeletes;

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
