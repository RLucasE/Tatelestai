<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class FoodEstablishment extends Model
{
    /** @use HasFactory<\Database\Factories\FoodEstablishmentFactory> */
    use HasFactory;
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'google_place_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function establishmentType()
    {
        return $this->belongsTo(EstablishmentType::class, 'establishment_type_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function verificationFiles()
    {
        return $this->hasMany(EstablishmentVerificationFile::class, 'food_establishment_id');
    }
}
