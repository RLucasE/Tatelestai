<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstablishmentVerificationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_establishment_id',
        'file_path',
        'file_type',
    ];

    protected $casts = [
        //
    ];

    /**
     * Relación con FoodEstablishment
     */
    public function foodEstablishment()
    {
        return $this->belongsTo(FoodEstablishment::class, 'food_establishment_id');
    }
}
