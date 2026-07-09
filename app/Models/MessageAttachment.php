<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'business_id',
        'provider',
        'provider_attachment_id',
        'filename',
        'mime_type',
        'size',
        'disk',
        'storage_path',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
