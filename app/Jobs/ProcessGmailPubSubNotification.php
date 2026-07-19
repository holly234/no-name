<?php

namespace App\Jobs;

use App\Services\GmailConnectionService;
use App\Support\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessGmailPubSubNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly array $payload)
    {
        $this->onQueue(QueueName::SYNC);
    }

    public function handle(GmailConnectionService $gmailConnectionService): array
    {
        return $gmailConnectionService->syncFromPubSubNotification($this->payload);
    }
}
