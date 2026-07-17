<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditTransaction extends Model
{
    protected $fillable = ['business_id', 'type', 'credits', 'balance_after', 'description', 'reference', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
