<?php

namespace App\Console\Commands;

use App\Models\AutomationLog;
use App\Models\TeamInvite;
use Illuminate\Console\Command;

class PruneOperationalData extends Command
{
    protected $signature = 'ops:prune';

    protected $description = 'Prune expired operational records without deleting customer conversations';

    public function handle(): int
    {
        $successfulLogs = AutomationLog::query()
            ->whereNotIn('status', ['failed', 'error'])
            ->where('created_at', '<', now()->subDays((int) config('operations.retention.successful_automation_logs_days', 30)))
            ->delete();
        $failedLogs = AutomationLog::query()
            ->whereIn('status', ['failed', 'error'])
            ->where('created_at', '<', now()->subDays((int) config('operations.retention.failed_automation_logs_days', 90)))
            ->delete();
        $invites = TeamInvite::query()
            ->where(function ($query) {
                $query->whereIn('status', ['accepted', 'cancelled'])
                    ->orWhere(fn ($pending) => $pending->where('status', 'pending')->where('expires_at', '<', now()));
            })
            ->where('updated_at', '<', now()->subDays((int) config('operations.retention.team_invites_days', 30)))
            ->delete();

        $this->info("Pruned {$successfulLogs} routine logs, {$failedLogs} old error logs, and {$invites} expired invitations.");

        return self::SUCCESS;
    }
}
