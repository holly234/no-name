<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'price',
        'availability',
        'ai_notes',
    ];
}
