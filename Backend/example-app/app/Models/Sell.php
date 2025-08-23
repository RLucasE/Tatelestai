<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $guarded = ['id'];
    /** @use HasFactory<\Database\Factories\SellFactory> */
    use HasFactory;

    public function sellDetails()
    {
        return $this->hasMany(SellDetail::class);
    }

    public function foodEstablishment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FoodEstablishment::class,'sold_by');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class,'bought_by');
    }
}
