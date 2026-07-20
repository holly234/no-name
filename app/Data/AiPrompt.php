<?php

namespace App\Data;

class AiPrompt
{
    public function __construct(
        public readonly string $system,
        public readonly string $message,
    ) {}
}
