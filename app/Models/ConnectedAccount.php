<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectedAccount extends Model
{
    protected $fillable = [
        'business_id',
        'platform',
        'account_name',
        'external_account_id',
        'page_id',
        'phone_number_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_meta',
        'status',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'provider_meta' => 'array',
            'connected_at' => 'datetime',
        ];
    }
}
