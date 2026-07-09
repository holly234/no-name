<?php

namespace App\Services;

class MetaConnectionService
{
    public function createFakeConnectionPayload(string $platform): array
    {
        return [
            'platform' => $platform,
            'status' => 'placeholder',
        ];
    }
}
