<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\PackTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'allergens' => 'array',
        'estimated_weight_kg' => 'decimal:2',
    ];

    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}
