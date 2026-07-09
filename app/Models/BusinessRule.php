<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessRule extends Model
{
    protected $fillable = [
        'business_id',
        'rule_type',
        'title',
        'content',
    ];
}
