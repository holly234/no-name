<x-app-layout>
    <div class="space-y-6">
        <section class="content-card p-6 lg:p-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">Workspace overview</p>
                    <h2 class="mt-3 max-w-2xl text-3xl font-bold tracking-normal text-[#111827] lg:text-4xl">Customer conversations at a glance</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#6B7280]">
                        Monitor open conversations, staff attention, automated handling, and customer response queues from one organized workspace.
                    </p>
                </div>

                <a href="{{ route('dashboard.inbox') }}" class="inline-flex w-fit items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                    Open inbox
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                </a>
            </div>

            <div class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'All conversations', 'value' => $counts['conversations'], 'color' => '#6B7280', 'copy' => 'Total inbox history'],
                    ['label' => 'Needs Human', 'value' => $counts['needsHuman'], 'color' => '#EC4899', 'copy' => 'Staff should review'],
                    ['label' => 'Smart handling', 'value' => $counts['aiHandling'], 'color' => '#10B981', 'copy' => 'Routed automatically'],
                    ['label' => 'Waiting', 'value' => $counts['waiting'], 'color' => '#F59E0B', 'copy' => 'Customer response pending'],
                ] as $summary)
                    <div class="rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $summary['color'] }}"></span>
                            <span class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">{{ $summary['copy'] }}</span>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-[#6B7280]">{{ $summary['label'] }}</p>
                        <p class="mt-1 text-4xl font-bold text-[#111827]">{{ $summary['value'] }}</p>
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
