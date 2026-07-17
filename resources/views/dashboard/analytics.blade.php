<x-app-layout>
    <div class="space-y-6">
        <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#2563EB]">Last 30 days</p><h2 class="mt-2 text-2xl font-black text-[#111827]">Workspace performance</h2><p class="mt-1 text-sm text-[#6B7280]">A simple operational view from your real inbox data.</p></div>
        <section class="grid gap-4 sm:grid-cols-3">
            @foreach ([['Conversations', $conversationCount], ['Messages', $messageCount], ['Inbound messages', $inboundCount]] as [$label, $value])
                <article class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">{{ $label }}</p><p class="mt-3 text-3xl font-black">{{ number_format($value) }}</p></article>
            @endforeach
        </section>
        <section class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-sm">
            <h3 class="font-bold">Conversations by channel</h3>
            <div class="mt-5 space-y-3">
                @forelse ($channelBreakdown as $channel)
                    @php $width = $conversationCount > 0 ? max(4, round(($channel->total / $conversationCount) * 100)) : 0; @endphp
                    <div><div class="mb-1 flex justify-between text-sm"><span class="font-semibold">{{ $channel->channel }}</span><span>{{ number_format($channel->total) }}</span></div><div class="h-2 overflow-hidden rounded-full bg-[#EEF0F3]"><div class="h-full rounded-full bg-[#2563EB]" style="width: {{ $width }}%"></div></div></div>
                @empty
                    <div class="rounded-xl border border-dashed border-[#D1D5DB] py-12 text-center text-sm text-[#6B7280]">Connect a channel and receive messages to populate analytics.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
