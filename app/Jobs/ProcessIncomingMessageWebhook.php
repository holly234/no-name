<?php

namespace App\Jobs;

use App\Services\MessageIngestionService;
use App\Support\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingMessageWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly array $payload)
    {
        $this->onQueue(QueueName::WEBHOOKS);
    }

    public function handle(MessageIngestionService $messageIngestionService): array
    {
        $conversation = $messageIngestionService->ingest($this->payload + ['source' => 'webhook']);

        return [
            'conversation_id' => $conversation->id,
            'state' => $conversation->status,
        ];
    }
}
