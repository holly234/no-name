<?php

namespace App\Contracts;

use App\Data\AiGeneration;
use App\Data\AiPrompt;

interface AiProvider
{
    public function generate(AiPrompt $prompt): AiGeneration;
}
