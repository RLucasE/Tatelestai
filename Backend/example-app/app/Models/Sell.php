<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $guarded = ['id'];

    public function sellDetails()
    {
        return $this->hasMany(SellDetail::class);
    }
}
