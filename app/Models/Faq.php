<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'business_id',
        'question',
        'answer',
        'category',
    ];
}
