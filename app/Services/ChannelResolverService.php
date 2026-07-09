<?php

namespace App\Services;

class ChannelResolverService
{
    public function resolve(array $payload): ?string
    {
        return $payload['platform'] ?? $payload['channel'] ?? null;
    }
}
