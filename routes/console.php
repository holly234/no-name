<?php

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Services\GmailConnectionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gmail:sync {--mailbox=inbox : Gmail mailbox to sync} {--limit=20 : Number of recent messages to inspect per account}', function (GmailConnectionService $gmailConnectionService) {
    $mailbox = (string) $this->option('mailbox');
    $limit = max(1, min(100, (int) $this->option('limit')));

    if (! in_array($mailbox, GmailConnectionService::MAILBOXES, true) || $mailbox === GmailConnectionService::MAILBOX_ALL) {
        $mailbox = GmailConnectionService::MAILBOX_INBOX;
    }

    $accounts = ConnectedAccount::query()
        ->where('platform', 'gmail')
        ->where('status', 'connected')
        ->get();

    if ($accounts->isEmpty()) {
        $this->components->info('No connected Gmail accounts found.');

        return self::SUCCESS;
    }

    $totalImported = 0;
    $totalSkipped = 0;
    $failed = 0;

    foreach ($accounts as $account) {
        try {
            $result = $gmailConnectionService->syncRecentInboxMessages($account, $limit, $mailbox);
            $totalImported += $result['imported'];
            $totalSkipped += $result['skipped'];

            $this->line("{$account->account_name}: {$result['imported']} imported, {$result['skipped']} skipped.");
        } catch (\Throwable $exception) {
            report($exception);
            $failed++;

            AutomationLog::create([
                'business_id' => $account->business_id,
                'connected_account_id' => $account->id,
                'event_type' => 'gmail_auto_sync_failed',
                'status' => 'failed',
                'message' => 'Automatic Gmail sync failed.',
                'metadata' => [
                    'mailbox' => $mailbox,
                    'error' => $exception->getMessage(),
                ],
            ]);

            $this->components->warn("{$account->account_name}: sync failed.");
        }
    }

    $this->components->info("Gmail auto-sync complete: {$totalImported} imported, {$totalSkipped} skipped, {$failed} failed.");

    return $failed > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Sync recent Gmail inbox messages for every connected Gmail account');

Schedule::command('gmail:sync --mailbox=inbox --limit=20')
    ->everyMinute()
    ->withoutOverlapping();
