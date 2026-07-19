<?php

namespace App\Jobs;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Services\MessageIngestionService;
use App\Support\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMetaWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly array $payload)
    {
        $this->onQueue(QueueName::WEBHOOKS);
    }

    public function handle(MessageIngestionService $messageIngestionService): array
    {
        $processed = 0;

        if (in_array($this->payload['object'] ?? null, ['page', 'instagram'], true)) {
            return ['processed' => $this->processMessengerEvents($messageIngestionService)];
        }

        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $account = ConnectedAccount::where('platform', 'WhatsApp')
                    ->where('phone_number_id', (string) ($value['metadata']['phone_number_id'] ?? ''))
                    ->where('status', 'connected')
                    ->first();

                if (! $account) {
                    continue;
                }

                foreach ($value['messages'] ?? [] as $message) {
                    if (($message['type'] ?? null) !== 'text') {
                        continue;
                    }

                    $contact = collect($value['contacts'] ?? [])->firstWhere('wa_id', $message['from']);
                    $conversation = $messageIngestionService->ingest([
                        'business_id' => $account->business_id,
                        'channel' => 'WhatsApp',
                        'connected_account_id' => $account->id,
                        'external_account_id' => $account->external_account_id,
                        'customer_name' => $contact['profile']['name'] ?? $message['from'],
                        'customer_external_id' => $message['from'],
                        'body' => $message['text']['body'] ?? '',
                        'metadata' => [
                            'source' => 'meta_whatsapp',
                            'whatsapp_message_id' => $message['id'] ?? null,
                        ],
                    ]);

                    $this->logProcessed($account, 'WhatsApp message processed.', [
                        'conversation_id' => $conversation->id,
                        'message_id' => $message['id'] ?? null,
                    ]);

                    $processed++;
                }
            }
        }

        return ['processed' => $processed];
    }

    private function processMessengerEvents(MessageIngestionService $messageIngestionService): int
    {
        $platform = ($this->payload['object'] ?? null) === 'instagram' ? 'Instagram' : 'Facebook';
        $processed = 0;

        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $event) {
                $message = $event['message'] ?? [];
                if (($message['is_echo'] ?? false) || empty($message['text']) || empty($event['sender']['id'])) {
                    continue;
                }

                $assetId = (string) ($event['recipient']['id'] ?? $entry['id'] ?? '');
                $account = ConnectedAccount::where('platform', $platform)
                    ->where('status', 'connected')
                    ->where(function ($query) use ($assetId) {
                        $query->where('page_id', $assetId)->orWhere('external_account_id', $assetId);
                    })
                    ->first();

                if (! $account) {
                    continue;
                }

                $senderId = (string) $event['sender']['id'];
                $conversation = $messageIngestionService->ingest([
                    'business_id' => $account->business_id,
                    'channel' => $platform,
                    'connected_account_id' => $account->id,
                    'external_account_id' => $account->external_account_id,
                    'customer_name' => $platform.' customer',
                    'customer_external_id' => $senderId,
                    'body' => (string) $message['text'],
                    'metadata' => [
                        'source' => $platform === 'Instagram' ? 'meta_instagram' : 'meta_messenger',
                        'meta_message_id' => $message['mid'] ?? null,
                        'meta_timestamp' => $event['timestamp'] ?? null,
                    ],
                ]);

                $this->logProcessed($account, $platform.' message processed.', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message['mid'] ?? null,
                ]);

                $processed++;
            }
        }

        return $processed;
    }

    private function logProcessed(ConnectedAccount $account, string $message, array $metadata): void
    {
        AutomationLog::create([
            'business_id' => $account->business_id,
            'connected_account_id' => $account->id,
            'event_type' => 'meta_webhook',
            'status' => 'success',
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
