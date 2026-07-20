<?php

namespace App\Data;

class AiCreditReservation
{
    public function __construct(
        public readonly string $reference,
        public readonly int $credits,
    ) {}
}
