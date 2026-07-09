<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    protected $fillable = [
        'business_id',
        'title',
        'body',
        'shortcut',
    ];
}
