<?php

namespace App\Filament\Pages;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('owner-panel:system-health', now()->addSeconds(30), fn (): array => [
            'failedJobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'pendingJobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'staleReservedJobs' => Schema::hasTable('jobs') ? DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<=', now()->subMinutes((int) config('operations.health.stale_reserved_job_minutes', 10))->timestamp)
                ->count() : 0,
            'recentErrors' => AutomationLog::query()
                ->whereIn('status', ['failed', 'error'])
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'expiredConnections' => ConnectedAccount::query()
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<=', now())
                ->count(),
            'disconnectedAccounts' => ConnectedAccount::query()->where('status', 'disconnected')->count(),
            'lastError' => AutomationLog::query()
                ->with('business')
                ->whereIn('status', ['failed', 'error'])
                ->latest()
                ->first(),
        ]);
    }
}
