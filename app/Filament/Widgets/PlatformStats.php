<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = Cache::remember('owner-panel:platform-stats', now()->addSeconds(45), function (): array {
            $businessStats = Business::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN is_suspended = 1 THEN 1 ELSE 0 END) as suspended')
                ->first();

            $conversationStats = Conversation::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as needs_human', [Conversation::STATE_NEEDS_HUMAN])
                ->first();

            return [
                'users' => User::query()->count(),
                'workspaces' => (int) ($businessStats->total ?? 0),
                'suspendedWorkspaces' => (int) ($businessStats->suspended ?? 0),
                'conversations' => (int) ($conversationStats->total ?? 0),
                'needsHuman' => (int) ($conversationStats->needs_human ?? 0),
                'activeConnections' => ConnectedAccount::query()->where('status', 'connected')->count(),
                'messagesToday' => Message::query()
                    ->where('created_at', '>=', today())
                    ->where('created_at', '<', today()->addDay())
                    ->count(),
                'aiEnabledWorkspaces' => Business::query()
                    ->whereHas('aiSetting', fn ($query) => $query->where('auto_reply_enabled', true))
                    ->count(),
            ];
        });

        return [
            Stat::make('Users', $stats['users'])
                ->description('Registered platform users'),
            Stat::make('Workspaces', $stats['workspaces'])
                ->description($stats['suspendedWorkspaces'].' suspended'),
            Stat::make('Conversations', $stats['conversations'])
                ->description($stats['needsHuman'].' need human attention'),
            Stat::make('Active connections', $stats['activeConnections'])
                ->description('Across all workspaces'),
            Stat::make('Messages today', $stats['messagesToday'])
                ->description('Inbound and outbound traffic'),
            Stat::make('AI-enabled workspaces', $stats['aiEnabledWorkspaces'])
                ->description('Automatic replies enabled'),
        ];
    }
}
