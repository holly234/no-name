<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'external_id',
        'channel',
        'notes',
        'avatar_disk',
        'avatar_path',
        'avatar_url',
        'avatar_provider_id',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path) {
            return Storage::disk($this->avatar_disk ?: 'public')->url($this->avatar_path);
        }

        return $this->avatar_url;
    }
}
