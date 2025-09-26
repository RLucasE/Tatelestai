<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Support\HigherOrderCollectionProxy|mixed $state
 */
class EstablishmentType extends Model
{
    /** @use HasFactory<\Database\Factories\EstablishmentTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'state',
        'description'
    ];
}
