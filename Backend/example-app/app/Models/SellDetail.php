<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellDetail extends Model
{
    protected $guarded = ['id'];

    use HasFactory;

    protected $casts = [
        'allergens' => 'array',
        'estimated_weight_kg' => 'float',
        'minimum_value' => 'float',
    ];

    public function sell()
    {
        return $this->belongsTo(Sell::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
