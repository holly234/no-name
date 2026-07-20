<?php

namespace App\Data;

class AiGeneration
{
    public function __construct(
        public readonly string $reply,
        public readonly string $state,
        public readonly float $confidence,
        public readonly bool $requiresHuman,
        public readonly ?string $reason,
        public readonly ?string $intent,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly float $providerCostUsd,
        public readonly int $latencyMs,
        public readonly array $metadata = [],
    ) {}
}
