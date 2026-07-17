<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvite extends Model
{
    protected $fillable = [
        'business_id',
        'email',
        'role',
        'token',
        'status',
        'invited_by',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
