<x-app-layout>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl bg-[#111827] p-6 text-white shadow-sm sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-300">AI wallet</p>
                    <p class="mt-3 text-4xl font-black">{{ number_format($wallet->balance) }}</p>
                    <p class="mt-1 text-sm text-slate-300">credits available</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                    Credit purchases will appear here when checkout goes live.
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['This month', number_format($monthCredits), 'Credits consumed'],
                ['AI replies', number_format($monthReplies), 'Completed this month'],
                ['Model tokens', number_format($monthTokens), 'Input and output'],
                ['Lifetime used', number_format($wallet->lifetime_used), 'All recorded usage'],
            ] as [$label, $value, $caption])
                <article class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">{{ $label }}</p>
                    <p class="mt-3 text-2xl font-black text-[#111827]">{{ $value }}</p>
                    <p class="mt-1 text-xs text-[#6B7280]">{{ $caption }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,.8fr)]">
            <article class="overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-sm">
                <div class="border-b border-[#E5E7EB] px-5 py-4">
                    <h2 class="font-bold text-[#111827]">Usage history</h2>
                    <p class="mt-1 text-sm text-[#6B7280]">Every AI response will record its model, tokens and charged credits.</p>
                </div>
                @if ($usage->isEmpty())
                    <div class="px-5 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#EFF6FF] text-[#2563EB]">✦</div>
                        <h3 class="mt-4 font-bold text-[#111827]">No AI usage yet</h3>
                        <p class="mx-auto mt-1 max-w-md text-sm text-[#6B7280]">Usage starts appearing after the Laravel AI agent sends its first metered reply.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-left text-sm">
                            <thead class="bg-[#F9FAFB] text-xs uppercase text-[#6B7280]"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Model</th><th class="px-5 py-3">Tokens</th><th class="px-5 py-3">Credits</th><th class="px-5 py-3">Status</th></tr></thead>
                            <tbody class="divide-y divide-[#E5E7EB]">
                                @foreach ($usage as $record)
                                    <tr><td class="px-5 py-4">{{ $record->created_at->format('d M Y, H:i') }}</td><td class="px-5 py-4 font-semibold">{{ $record->provider }} / {{ $record->model }}</td><td class="px-5 py-4">{{ number_format($record->input_tokens + $record->output_tokens) }}</td><td class="px-5 py-4">{{ number_format($record->credits_used) }}</td><td class="px-5 py-4 capitalize">{{ $record->status }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-[#E5E7EB] p-4">{{ $usage->links() }}</div>
                @endif
            </article>

            <article class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm">
                <h2 class="font-bold text-[#111827]">Wallet activity</h2>
                <p class="mt-1 text-sm text-[#6B7280]">Purchases, bonuses, usage and refunds.</p>
                <div class="mt-5 space-y-3">
                    @forelse ($transactions as $transaction)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-[#F9FAFB] p-3">
                            <div><p class="text-sm font-bold">{{ $transaction->description }}</p><p class="text-xs text-[#6B7280]">{{ $transaction->created_at->format('d M Y') }}</p></div>
                            <span class="font-bold {{ $transaction->credits >= 0 ? 'text-[#059669]' : 'text-[#DC2626]' }}">{{ $transaction->credits >= 0 ? '+' : '' }}{{ number_format($transaction->credits) }}</span>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-[#D1D5DB] px-4 py-10 text-center text-sm text-[#6B7280]">No wallet activity yet.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
