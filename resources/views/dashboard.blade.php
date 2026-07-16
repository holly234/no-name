<x-app-layout>
    <div class="space-y-6">
        <section class="content-card overflow-hidden">
            <div class="border-b border-[#E5E7EB] bg-white px-5 py-5 lg:px-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#ECFDF5] px-2.5 py-1 text-xs font-bold text-[#047857]"><span class="h-2 w-2 rounded-full bg-[#10B981]"></span>Workspace live</span>
                    </div>
                    <h2 class="mt-3 max-w-2xl text-2xl font-bold tracking-tight text-[#111827] lg:text-3xl">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">
                        Here is what is happening across your customer conversations today.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('dashboard.accounts') }}" class="inline-flex items-center justify-center rounded-xl border border-[#E5E7EB] bg-white px-4 py-2.5 text-sm font-bold text-[#374151] transition hover:bg-[#F5F6F8]">Connect channel</a>
                    <a href="{{ route('dashboard.inbox') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#10B981] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#059669]">
                        Open inbox
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                    </a>
                </div>
            </div>
            </div>

            <div class="grid gap-px bg-[#E5E7EB] sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'All conversations', 'value' => $counts['conversations'], 'color' => '#6B7280', 'copy' => 'Total inbox history', 'arrow' => '→'],
                    ['label' => 'Needs human', 'value' => $counts['needsHuman'], 'color' => '#EC4899', 'copy' => 'Staff should review', 'arrow' => '!'],
                    ['label' => 'AI handling', 'value' => $counts['aiHandling'], 'color' => '#10B981', 'copy' => 'Routed automatically', 'arrow' => '✦'],
                    ['label' => 'Waiting', 'value' => $counts['waiting'], 'color' => '#F59E0B', 'copy' => 'Customer response pending', 'arrow' => '○'],
                ] as $summary)
                    <div class="bg-white p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl text-sm font-black" style="background: color-mix(in srgb, {{ $summary['color'] }} 12%, white); color: {{ $summary['color'] }}">{{ $summary['arrow'] }}</span>
                            <span class="text-[0.68rem] font-bold uppercase tracking-[0.12em] text-[#9CA3AF]">{{ $summary['copy'] }}</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-[#111827]">{{ $summary['value'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-[#6B7280]">{{ $summary['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="content-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-[#E5E7EB] px-5 py-4">
                    <div>
                        <h3 class="font-bold text-[#111827]">Recent conversations</h3>
                        <p class="mt-1 text-sm text-[#6B7280]">Latest threads across connected channels.</p>
                    </div>
                </div>
                <div class="divide-y divide-[#E5E7EB]">
                    @forelse ($recentConversations as $conversation)
                        @php
                            $statusClass = match ($conversation->status) {
                                'AI Handling' => 'bg-[#ECFDF5] text-[#047857]',
                                'Waiting' => 'bg-[#FFFBEB] text-[#B45309]',
                                'Needs Human' => 'bg-pink-50 text-[#BE185D]',
                                default => 'bg-[#EEF0F3] text-[#6B7280]',
                            };
                        @endphp
                        <a href="{{ route('dashboard.inbox', ['conversation' => $conversation->id]) }}" class="block px-5 py-4 transition hover:bg-[#F5F6F8]">
                            <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#EEF0F3] text-sm font-bold text-[#111827]">{{ \Illuminate\Support\Str::of($conversation->customer_name)->substr(0, 1) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-[#111827]">{{ $conversation->customer_name }}</p>
                                            <p class="mt-0.5 truncate text-sm text-[#6B7280]">{{ $conversation->latestMessage?->body ?? 'No messages yet.' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <span class="state-pill {{ $statusClass }}"><span class="state-dot"></span>{{ $conversation->status }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-sm text-[#6B7280]">No conversations yet.</p>
                    @endforelse
                </div>
            </section>

            <div class="space-y-6">
                <section class="content-card overflow-hidden">
                    <div class="border-b border-[#E5E7EB] px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-[#111827]">AI agent</h3>
                                <p class="mt-1 text-sm text-[#6B7280]">Automation readiness</p>
                            </div>
                            <span class="rounded-full bg-[#FFFBEB] px-2.5 py-1 text-xs font-bold text-[#B45309]">Setup</span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="flex items-end justify-between gap-3">
                            <div><p class="text-3xl font-bold text-[#111827]">0</p><p class="text-xs font-semibold text-[#6B7280]">AI credits available</p></div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#ECFDF5] text-xl text-[#047857]">✦</span>
                        </div>
                        <a href="{{ route('dashboard.ai-settings') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-[#111827] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-black">Configure AI agent</a>
                    </div>
                </section>

                <section class="content-card p-5">
                    <h3 class="font-bold text-[#111827]">Workflow health</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-xl bg-[#F5F6F8] p-4 text-[#374151]">Routine enquiries are organized into channel and state filters.</div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4 text-[#374151]">Human takeover keeps sensitive threads visible to staff.</div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4 text-[#374151]">Customer history stays attached to each conversation.</div>
                    </div>
                </section>

                <section class="content-card overflow-hidden">
                    <div class="border-b border-[#E5E7EB] px-5 py-4">
                        <h3 class="font-bold text-[#111827]">Setup checklist</h3>
                        <p class="mt-1 text-sm text-[#6B7280]">MVP readiness snapshot.</p>
                    </div>
                    <div class="space-y-3 p-5 text-sm">
                        @foreach ([
                            ['label' => 'Workspace created', 'status' => 'Done'],
                            ['label' => 'Demo accounts connected', 'status' => 'Done'],
                            ['label' => 'Knowledge base seeded', 'status' => 'Done'],
                            ['label' => 'Real Meta API', 'status' => 'Later'],
                        ] as $item)
                            <div class="flex items-center justify-between rounded-xl bg-[#F5F6F8] px-3 py-3">
                                <span class="font-semibold text-[#374151]">{{ $item['label'] }}</span>
                                <span class="font-bold {{ $item['status'] === 'Done' ? 'text-[#10B981]' : 'text-[#6B7280]' }}">{{ $item['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
