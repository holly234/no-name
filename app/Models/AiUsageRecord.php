<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageRecord extends Model
{
    protected $fillable = ['business_id', 'conversation_id', 'message_id', 'provider', 'model', 'input_tokens', 'output_tokens', 'credits_used', 'provider_cost_usd', 'latency_ms', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['provider_cost_usd' => 'decimal:6', 'metadata' => 'array'];
    }
}
