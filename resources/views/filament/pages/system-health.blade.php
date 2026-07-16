<x-filament-panels::page>
    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
        @foreach ([
            ['Pending queue jobs', $pendingJobs, $pendingJobs > 100 ? 'warning' : 'healthy'],
            ['Failed queue jobs', $failedJobs, $failedJobs > 0 ? 'attention needed' : 'healthy'],
            ['Errors in 24 hours', $recentErrors, $recentErrors > 0 ? 'review logs' : 'healthy'],
            ['Expired connections', $expiredConnections, $expiredConnections > 0 ? 'reconnect required' : 'healthy'],
        ] as [$label, $value, $state])
            <div class="fi-section" style="padding:1.25rem;border-radius:1rem">
                <div style="font-size:.8rem;color:#9ca3af">{{ $label }}</div>
                <div style="font-size:1.75rem;font-weight:700;margin:.35rem 0">{{ number_format($value) }}</div>
                <div style="font-size:.75rem;color:{{ $state === 'healthy' ? '#10b981' : '#f59e0b' }}">{{ ucfirst($state) }}</div>
            </div>
        @endforeach
    </div>

    <div class="fi-section" style="padding:1.5rem;border-radius:1rem">
        <h2 style="font-size:1.1rem;font-weight:700">Latest recorded error</h2>
        @if ($lastError)
            <div style="margin-top:1rem;display:grid;gap:.6rem">
                <div><span style="color:#9ca3af">Workspace:</span> {{ $lastError->business?->name ?? 'Unknown' }}</div>
                <div><span style="color:#9ca3af">Event:</span> {{ $lastError->event_type }}</div>
                <div><span style="color:#9ca3af">When:</span> {{ $lastError->created_at->diffForHumans() }}</div>
                <div style="padding:1rem;border-radius:.7rem;background:rgba(239,68,68,.08);color:#f87171">{{ $lastError->error_details ?: $lastError->message }}</div>
            </div>
        @else
            <p style="color:#10b981;margin-top:.75rem">No failed automation event has been recorded.</p>
        @endif
    </div>

    <div class="fi-section" style="padding:1.5rem;border-radius:1rem">
        <h2 style="font-size:1.1rem;font-weight:700">Production checks</h2>
        <div style="display:grid;gap:.7rem;margin-top:1rem">
            @foreach (['Queue worker is running', 'Scheduler runs every minute', 'Webhook endpoints are reachable', 'Failed-job alerts are configured', 'Database backups are verified', 'AI and payment secrets are stored outside source control'] as $check)
                <div style="display:flex;gap:.7rem;align-items:center"><span style="color:#6b7280">○</span><span>{{ $check }}</span><span style="margin-left:auto;color:#9ca3af;font-size:.75rem">Verify on VPS</span></div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
