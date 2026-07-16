<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::query()->count())
                ->description('Registered platform users'),
            Stat::make('Workspaces', Business::query()->count())
                ->description(Business::query()->where('is_suspended', true)->count().' suspended'),
            Stat::make('Conversations', Conversation::query()->count())
                ->description(Conversation::query()->where('status', Conversation::STATE_NEEDS_HUMAN)->count().' need human attention'),
            Stat::make('Active connections', ConnectedAccount::query()->where('status', 'connected')->count())
                ->description('Across all workspaces'),
            Stat::make('Messages today', Message::query()->whereDate('created_at', today())->count())
                ->description('Inbound and outbound traffic'),
            Stat::make('AI-enabled workspaces', Business::query()->whereHas('aiSetting', fn ($query) => $query->where('auto_reply_enabled', true))->count())
                ->description('Automatic replies enabled'),
        ];
    }
}
