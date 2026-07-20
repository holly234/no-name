<?php

use App\Jobs\SyncGmailAccount;
use App\Models\AutomationLog;
use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Services\AiCreditLedgerService;
use App\Services\AiReplyRecoveryService;
use App\Services\GmailConnectionService;
use App\Support\QueueDispatch;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gmail:sync {--mailbox=inbox : Gmail mailbox to sync} {--limit=20 : Number of recent messages to inspect per account}', function () {
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
    $queued = 0;
    $runImmediately = QueueDispatch::shouldRunInline();

    foreach ($accounts as $account) {
        try {
            $job = new SyncGmailAccount($account->id, $limit, $mailbox);

            if ($runImmediately) {
                $result = QueueDispatch::dispatch($job);
                $totalImported += $result['imported'] ?? 0;
                $totalSkipped += $result['skipped'] ?? 0;

                $this->line("{$account->account_name}: {$result['imported']} imported, {$result['skipped']} skipped.");
            } else {
                QueueDispatch::dispatch($job);
                $queued++;

                $this->line("{$account->account_name}: queued.");
            }
        } catch (Throwable $exception) {
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

    $summary = $runImmediately
        ? "Gmail auto-sync complete: {$totalImported} imported, {$totalSkipped} skipped, {$failed} failed."
        : "Gmail auto-sync queued: {$queued} queued, {$failed} failed.";

    $this->components->info($summary);

    return $failed > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Sync recent Gmail inbox messages for every connected Gmail account');

Artisan::command('gmail:renew-watch {--force : Renew every connected Gmail watch even if it is not close to expiring}', function (GmailConnectionService $gmailConnectionService) {
    if (! config('services.gmail.pubsub_topic')) {
        $this->components->warn('GMAIL_PUBSUB_TOPIC is not configured; skipping Gmail watch renewal.');

        return self::SUCCESS;
    }

    $force = (bool) $this->option('force');
    $accounts = ConnectedAccount::query()
        ->where('platform', 'gmail')
        ->where('status', 'connected')
        ->get();

    if ($accounts->isEmpty()) {
        $this->components->info('No connected Gmail accounts found.');

        return self::SUCCESS;
    }

    $renewed = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($accounts as $account) {
        $expiration = (int) ($account->provider_meta['watch_expiration'] ?? 0);
        $expiresAt = $expiration > 0 ? Carbon::createFromTimestampMs($expiration) : null;
        $shouldRenew = $force || ! $expiresAt || $expiresAt->lte(now()->addDay());

        if (! $shouldRenew) {
            $skipped++;
            $this->line("{$account->account_name}: watch still valid until {$expiresAt->toDateTimeString()}.");

            continue;
        }

        try {
            $response = $gmailConnectionService->registerWatchIfConfigured($account);
            $renewed++;

            AutomationLog::create([
                'business_id' => $account->business_id,
                'connected_account_id' => $account->id,
                'event_type' => 'gmail_watch_renewed',
                'status' => 'success',
                'message' => 'Gmail Pub/Sub watch renewed.',
                'metadata' => ['response' => $response],
            ]);

            $this->line("{$account->account_name}: watch renewed.");
        } catch (Throwable $exception) {
            report($exception);
            $failed++;

            AutomationLog::create([
                'business_id' => $account->business_id,
                'connected_account_id' => $account->id,
                'event_type' => 'gmail_watch_renew_failed',
                'status' => 'failed',
                'message' => 'Gmail Pub/Sub watch renewal failed.',
                'metadata' => ['error' => $exception->getMessage()],
            ]);

            $this->components->warn("{$account->account_name}: watch renewal failed.");
        }
    }

    $this->components->info("Gmail watch renewal complete: {$renewed} renewed, {$skipped} skipped, {$failed} failed.");

    return $failed > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Renew Gmail Pub/Sub watches before they expire');

Artisan::command('ai:credits:grant {business : Workspace ID} {credits : Number of promotional credits} {--reference= : Unique external reference}', function (AiCreditLedgerService $ledger) {
    $business = Business::findOrFail((int) $this->argument('business'));
    $credits = (int) $this->argument('credits');

    if ($credits <= 0) {
        $this->components->error('Credits must be greater than zero.');

        return self::FAILURE;
    }

    $transaction = $ledger->grant(
        $business,
        $credits,
        'Promotional AI credits granted by platform operator',
        $this->option('reference') ?: null,
        ['source' => 'artisan']
    );

    $this->components->info(number_format($credits).' credits granted to '.$business->name.'. Balance: '.number_format($transaction->balance_after).'.');

    return self::SUCCESS;
})->purpose('Grant promotional AI credits to a workspace');

Artisan::command('ai:recover-unanswered {--limit=50 : Maximum conversations to recover per run}', function (AiReplyRecoveryService $recovery) {
    $queued = $recovery->recover((int) $this->option('limit'));

    $this->components->info("Queued {$queued} unanswered AI conversations for recovery.");

    return self::SUCCESS;
})->purpose('Recover AI-controlled conversations that never received a reply');

Schedule::command('gmail:sync --mailbox=inbox --limit=20')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('gmail:renew-watch')
    ->dailyAt('02:15')
    ->withoutOverlapping();

Schedule::command('ai:recover-unanswered --limit=50')
    ->everyMinute()
    ->withoutOverlapping();
