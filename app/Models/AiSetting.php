<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'business_id',
        'assistant_name',
        'tone',
        'auto_reply_enabled',
        'human_takeover_enabled',
        'business_hours_enabled',
        'fallback_response',
        'escalation_instructions',
        'never_say',
        'handover_rules',
    ];

    protected function casts(): array
    {
        return [
            'auto_reply_enabled' => 'boolean',
            'human_takeover_enabled' => 'boolean',
            'business_hours_enabled' => 'boolean',
        ];
    }
}
