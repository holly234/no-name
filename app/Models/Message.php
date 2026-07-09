<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'business_id',
        'direction',
        'sender_type',
        'body',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
