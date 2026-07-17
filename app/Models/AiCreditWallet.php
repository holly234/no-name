<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditWallet extends Model
{
    protected $fillable = ['business_id', 'balance', 'lifetime_purchased', 'lifetime_used'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
