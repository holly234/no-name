<?php

namespace App\Jobs;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Services\GmailConnectionService;
use App\Support\QueueName;
use App\Support\ProviderError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGmailAccount implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $accountId,
        public readonly int $limit = 20,
        public readonly string $mailbox = GmailConnectionService::MAILBOX_INBOX,
        public readonly string $eventType = 'gmail_auto_sync'
    ) {
        $this->onQueue(QueueName::SYNC);
    }

    public function handle(GmailConnectionService $gmailConnectionService): array
    {
        $account = ConnectedAccount::query()
            ->where('platform', 'gmail')
            ->where('status', 'connected')
            ->find($this->accountId);

        if (! $account) {
            return ['imported' => 0, 'skipped' => 0, 'status' => 'ignored'];
        }

        try {
            return $gmailConnectionService->syncRecentInboxMessages(
                $account,
                $this->limit,
                $this->mailbox
            ) + ['status' => 'processed'];
        } catch (\Throwable $exception) {
            ProviderError::report($exception, ['provider' => 'gmail', 'account_id' => $this->accountId]);

            AutomationLog::create([
                'business_id' => $account->business_id,
                'connected_account_id' => $account->id,
                'event_type' => $this->eventType.'_failed',
                'status' => 'failed',
                'message' => 'Gmail sync job failed.',
                'metadata' => [
                    'mailbox' => $this->mailbox,
                    'error' => ProviderError::message($exception),
                ],
            ]);

            throw $exception;
        }
    }
}
