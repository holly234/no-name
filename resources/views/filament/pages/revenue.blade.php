<x-filament-panels::page>
    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
        @foreach ([
            ['AI credit revenue', '₦0', 'Starts when credit checkout goes live'],
            ['Credits sold', '0', 'No completed credit purchases yet'],
            ['Credits consumed', '0', 'Usage metering is awaiting the AI agent'],
            ['Gross margin', '—', 'Calculated from provider cost and sales'],
        ] as [$label, $value, $hint])
            <div class="fi-section" style="padding:1.25rem;border-radius:1rem">
                <div style="font-size:.8rem;color:#9ca3af">{{ $label }}</div>
                <div style="font-size:1.75rem;font-weight:700;margin:.35rem 0">{{ $value }}</div>
                <div style="font-size:.75rem;color:#6b7280">{{ $hint }}</div>
            </div>
        @endforeach
    </div>

    <div class="fi-section" style="padding:1.5rem;border-radius:1rem">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:start;flex-wrap:wrap">
            <div>
                <h2 style="font-size:1.1rem;font-weight:700">Commercial engine</h2>
                <p style="color:#9ca3af;margin-top:.25rem;max-width:48rem">The unified inbox remains free. Businesses will prepay for AI credits, and every AI reply will be metered against their wallet.</p>
            </div>
            <span style="padding:.35rem .7rem;border-radius:999px;background:rgba(245,158,11,.15);color:#f59e0b;font-size:.75rem;font-weight:600">PRE-LAUNCH</span>
        </div>

        <div style="display:grid;gap:.8rem;margin-top:1.25rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
            @foreach ([
                ['1', 'Credit packages', 'Create NGN packages and decide how many AI credits each package contains.'],
                ['2', 'Wallet ledger', 'Keep an immutable record of purchases, bonuses, deductions and refunds.'],
                ['3', 'Usage metering', 'Record provider, model, input/output tokens and the true API cost of every reply.'],
                ['4', 'Payments', 'Credit wallets only after a verified Paystack or Flutterwave webhook.'],
                ['5', 'Margins', 'Compare NGN sales with converted AI-provider cost and monitor profit per workspace.'],
                ['6', 'Low-balance alerts', 'Warn businesses before agents stop and offer an immediate top-up.'],
            ] as [$number, $title, $text])
                <div style="border:1px solid rgba(107,114,128,.25);padding:1rem;border-radius:.8rem">
                    <div style="color:#10b981;font-weight:700;font-size:.75rem">PHASE {{ $number }}</div>
                    <div style="font-weight:650;margin:.25rem 0">{{ $title }}</div>
                    <div style="font-size:.8rem;color:#9ca3af;line-height:1.45">{{ $text }}</div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
