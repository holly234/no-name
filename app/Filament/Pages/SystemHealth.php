<?php

namespace App\Filament\Pages;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform operations';

    protected static ?string $navigationLabel = 'System health';

    protected string $view = 'filament.pages.system-health';

    public function getViewData(): array
    {
        return [
            'failedJobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'pendingJobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'recentErrors' => AutomationLog::query()->whereIn('status', ['failed', 'error'])->where('created_at', '>=', now()->subDay())->count(),
            'expiredConnections' => ConnectedAccount::query()->whereNotNull('token_expires_at')->where('token_expires_at', '<=', now())->count(),
            'lastError' => AutomationLog::query()->whereIn('status', ['failed', 'error'])->latest()->first(),
        ];
    }
}
