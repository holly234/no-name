<x-app-layout>
    @php
        $customerIdentityLabel = $selectedConversation ? match ($selectedConversation->channel) {
            'Instagram' => 'Instagram username',
            'Facebook' => 'Facebook username',
            'WhatsApp' => 'WhatsApp number',
            'Gmail' => 'Email address',
            'Telegram' => 'Telegram chat ID',
            default => 'Customer identifier',
        } : 'Customer identifier';
        $filterQuery = array_filter([
            'state' => $activeState === 'All' ? null : $activeState,
            'channel' => $activeChannel === 'All' ? null : $activeChannel,
            'q' => $search ?: null,
            'date' => $activeDate === 'all' ? null : $activeDate,
            'time' => $activeTime === 'all' ? null : $activeTime,
            'sort' => $activeSort === 'newest' ? null : $activeSort,
        ]);
        $dateOptions = [
            'all' => 'Any day',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
        ];
        $timeOptions = [
            'all' => 'Any time',
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'evening' => 'Evening',
            'night' => 'Night',
        ];
        $sortOptions = [
            'newest' => 'Newest',
            'oldest' => 'Oldest',
        ];
        $advancedFiltersActive = $activeDate !== 'all' || $activeTime !== 'all' || $activeSort !== 'newest';
        $compactStateLabels = [
            'All' => 'Inbox',
            \App\Models\Conversation::STATE_NEEDS_HUMAN => 'Needs reply',
            \App\Models\Conversation::STATE_AI_HANDLING => 'AI',
            \App\Models\Conversation::STATE_WAITING => 'Scheduled',
            \App\Models\Conversation::STATE_CLOSED => 'Done',
        ];
        $channelIconTone = [
            'All' => 'text-[#111827]',
            'Instagram' => 'text-[#dd2a7b]',
            'WhatsApp' => 'text-[#10B981]',
            'Facebook' => 'text-[#1877f2]',
            'Gmail' => 'text-[#ea4335]',
            'Telegram' => 'text-[#229ED9]',
        ];
    @endphp

    <div x-data="window.inboxPage()" data-inbox-version="{{ $inboxVersion }}" class="grid h-full min-h-0 w-full min-w-0 max-w-full grid-cols-[minmax(0,1fr)] overflow-hidden bg-[#F5F6F8] text-[#111827] lg:grid-cols-[410px_minmax(0,1fr)] xl:grid-cols-[410px_minmax(0,1fr)_320px]">
        <aside class="{{ $conversationIsOpen ? 'hidden' : 'flex' }} h-full min-h-0 w-full min-w-0 max-w-full flex-col overflow-hidden border-r border-[#E5E7EB] bg-white lg:flex">
            <div class="w-full max-w-full shrink-0 overflow-visible border-b border-[#E5E7EB] bg-white px-4 py-4 sm:px-5">
                <div class="flex w-full min-w-0 items-center gap-2.5">
                    <button type="button" class="mobile-menu-button lg:hidden" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                        <span class="mobile-menu-mark" aria-hidden="true"></span>
                    </button>
                    <form method="GET" action="{{ route('dashboard.inbox') }}" class="min-w-0 flex-1">
                        <input type="hidden" name="state" value="{{ $activeState }}">
                        <input type="hidden" name="channel" value="{{ $activeChannel }}">
                        <input type="hidden" name="date" value="{{ $activeDate }}">
                        <input type="hidden" name="time" value="{{ $activeTime }}">
                        <input type="hidden" name="sort" value="{{ $activeSort }}">
                        <label class="flex min-w-0 items-center gap-3 overflow-hidden rounded-xl border border-[#E5E7EB] bg-white px-3 py-3 text-[#6B7280] shadow-sm transition sm:px-4">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0">
                                <path d="m21 21-4.35-4.35"></path>
                                <circle cx="11" cy="11" r="7"></circle>
                            </svg>
                            <span class="sr-only">Search conversations</span>
                            <input name="q" value="{{ $search }}" autocomplete="off" placeholder="Search conversations" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-transparent focus:outline-none focus:ring-0 focus:shadow-none">
                            @if ($search !== '')
                                <a href="{{ route('dashboard.inbox', array_filter($filterQuery, fn ($value, $key) => $key !== 'q', ARRAY_FILTER_USE_BOTH)) }}" class="shrink-0 rounded-full px-1.5 text-xs font-bold text-[#6B7280] hover:bg-[#EEF0F3] hover:text-[#111827]" aria-label="Clear search">x</a>
                            @endif
                        </label>
                    </form>
                    <button type="button" x-on:click="filtersOpen = true" class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#E5E7EB] bg-white text-[#111827] shadow-sm transition hover:bg-[#F5F6F8]" aria-label="Open filters">
                        @if ($advancedFiltersActive)
                            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[#2563EB]"></span>
                        @endif
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M4 5h16l-6.5 7.2V18l-3 1.5v-7.3L4 5Z"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 grid w-full grid-cols-6 gap-1 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-1">
                    @foreach ($channelMeta as $channel => $meta)
                        @php
                            $isChannelActive = $activeChannel === $channel;
                            $channelUrl = route('dashboard.inbox', array_filter([
                                'state' => $activeState === 'All' ? null : $activeState,
                                'channel' => $channel === 'All' ? null : $channel,
                                'q' => $search ?: null,
                                'date' => $activeDate === 'all' ? null : $activeDate,
                                'time' => $activeTime === 'all' ? null : $activeTime,
                                'sort' => $activeSort === 'newest' ? null : $activeSort,
                            ]));
                        @endphp
                        <a href="{{ $channelUrl }}" title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}" class="group flex h-10 items-center justify-center rounded-lg transition {{ $isChannelActive ? 'bg-white shadow-sm ring-1 ring-[#E5E7EB]' : 'hover:bg-white' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-[#E5E7EB] {{ $channelIconTone[$channel] ?? 'text-[#111827]' }} {{ $isChannelActive ? 'ring-2 ring-[#2563EB]/20' : 'opacity-80 group-hover:opacity-100' }}">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4">
                                    {!! $meta['icon'] !!}
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="mt-3 grid w-full min-w-0 max-w-full grid-cols-5 gap-1 overflow-hidden rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-1 sm:mt-4">
                    @foreach ($filterMeta as $state => $meta)
                        @php
                            $count = $counts[$state] ?? 0;
                            $isActive = $activeState === $state;
                            $stateUrl = route('dashboard.inbox', array_filter([
                                'state' => $state === 'All' ? null : $state,
                                'channel' => $activeChannel === 'All' ? null : $activeChannel,
                                'q' => $search ?: null,
                                'date' => $activeDate === 'all' ? null : $activeDate,
                                'time' => $activeTime === 'all' ? null : $activeTime,
                                'sort' => $activeSort === 'newest' ? null : $activeSort,
                            ]));
                            $stateTone = match ($state) {
                                'All' => 'text-[#2563EB]',
                                \App\Models\Conversation::STATE_NEEDS_HUMAN => 'text-[#BE185D]',
                                \App\Models\Conversation::STATE_AI_HANDLING => 'text-[#047857]',
                                \App\Models\Conversation::STATE_WAITING => 'text-[#B45309]',
                                default => 'text-[#6B7280]',
                            };
                        @endphp
                        <a href="{{ $stateUrl }}" title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}" class="group flex h-10 min-w-0 items-center justify-center gap-1 rounded-lg text-xs font-bold transition sm:h-11 {{ $isActive ? 'bg-[#2563EB] text-white shadow-sm' : $stateTone.' hover:bg-white' }}">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                                {!! $meta['icon'] !!}
                            </svg>
                            <span class="sr-only">{{ $meta['label'] }}</span>
                            <span>{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div x-cloak x-show="filtersOpen" class="fixed inset-0 z-50 bg-[#111827]/10" x-on:click.self="filtersOpen = false" x-on:close-filters="filtersOpen = false">
                <div class="absolute inset-x-0 bottom-0 sm:left-1/2 sm:bottom-6 sm:max-w-[25rem] sm:-translate-x-1/2">
                <div x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full opacity-80" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-80" class="rounded-t-[1.4rem] border border-[#E5E7EB] bg-white px-5 pb-6 pt-3 shadow-2xl shadow-[#111827]/10 will-change-transform sm:rounded-[1.4rem]">
                    <div class="mx-auto h-1 w-11 rounded-full bg-[#D1D5DB]"></div>
                    <div class="mt-5 flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F5F6F8] text-[#111827]" aria-label="Filters">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M4 5h16l-6.5 7.2V18l-3 1.5v-7.3L4 5Z"></path>
                            </svg>
                        </div>
                        <button type="button" x-on:click="filtersOpen = false" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F5F6F8] text-[#6B7280] transition hover:bg-[#EEF0F3] hover:text-[#111827]" aria-label="Close filters">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('dashboard.inbox') }}" class="mt-6" data-filter-apply="true">
                        <input type="hidden" name="state" value="{{ $activeState }}">
                        <input type="hidden" name="channel" value="{{ $activeChannel }}">
                        @if ($search !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        <div class="grid gap-4">
                            <div x-data="window.inboxFilterMenu(@js($activeDate), @js($dateOptions))" x-on:click.outside="open = false" class="relative min-w-0">
                                <input type="hidden" name="date" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#6B7280]">Date</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#E5E7EB] bg-white px-3 text-left text-sm font-semibold text-[#374151] shadow-sm transition hover:bg-[#F5F6F8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#6B7280]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#E5E7EB] bg-white p-1 shadow-xl shadow-[#111827]/10">
                                    @foreach ($dateOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#F5F6F8]" x-bind:class="value === @js($value) ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#374151]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div x-data="window.inboxFilterMenu(@js($activeTime), @js($timeOptions))" x-on:click.outside="open = false" class="relative min-w-0">
                                <input type="hidden" name="time" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#6B7280]">Time</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#E5E7EB] bg-white px-3 text-left text-sm font-semibold text-[#374151] shadow-sm transition hover:bg-[#F5F6F8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#6B7280]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#E5E7EB] bg-white p-1 shadow-xl shadow-[#111827]/10">
                                    @foreach ($timeOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#F5F6F8]" x-bind:class="value === @js($value) ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#374151]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div x-data="window.inboxFilterMenu(@js($activeSort), @js($sortOptions))" x-on:click.outside="open = false" class="relative min-w-0">
                                <input type="hidden" name="sort" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#6B7280]">Sort by</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#E5E7EB] bg-white px-3 text-left text-sm font-semibold text-[#374151] shadow-sm transition hover:bg-[#F5F6F8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#6B7280]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#E5E7EB] bg-white p-1 shadow-xl shadow-[#111827]/10">
                                    @foreach ($sortOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#F5F6F8]" x-bind:class="value === @js($value) ? 'bg-[#EFF6FF] text-[#2563EB]' : 'text-[#374151]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end">
                            <a href="{{ route('dashboard.inbox', array_filter(['state' => $activeState === 'All' ? null : $activeState, 'channel' => $activeChannel === 'All' ? null : $activeChannel, 'q' => $search ?: null])) }}" class="rounded-xl bg-[#F5F6F8] px-7 py-3 text-sm font-bold text-[#6B7280] shadow-sm transition hover:bg-[#EEF0F3] hover:text-[#111827]">Reset</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div class="min-h-0 w-full max-w-full flex-1 overflow-y-auto overflow-x-hidden bg-white">
                @forelse ($conversations as $conversation)
                    @php
                        $intent = $conversation->getAttribute('intent');
                        $unreadCount = $conversation->getAttribute('unread_count');
                        $statusMeta = $conversation->getAttribute('status_meta');
                        $channel = $conversation->getAttribute('channel_meta');
                        $latestReplyDisabled = (bool) $conversation->getAttribute('reply_disabled');
                        $avatarUrl = $conversation->customer?->avatarUrl();
                    @endphp
                    <a href="{{ route('dashboard.inbox', $filterQuery + ['conversation' => $conversation->id]) }}" class="group block w-full min-w-0 max-w-full overflow-hidden px-4 transition hover:bg-[#F5F6F8] sm:px-5 {{ $selectedConversation?->id === $conversation->id ? 'bg-[#EFF6FF]' : '' }}">
                        <div class="flex w-full min-w-0 max-w-full gap-3 overflow-hidden border-b border-[#E5E7EB] py-3.5">
                            <span class="relative mt-1 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm sm:h-12 sm:w-12 {{ $avatarUrl ? 'bg-[#EEF0F3]' : $channel['class'] }}" title="{{ $conversation->channel }}" aria-label="{{ $conversation->channel }}">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="" class="h-full w-full object-cover">
                                    <span class="absolute bottom-0 right-0 flex h-5 w-5 items-center justify-center rounded-md border border-white {{ $channel['class'] }}">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3 w-3">
                                            {!! $channel['icon'] !!}
                                        </svg>
                                    </span>
                                @else
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
                                        {!! $channel['icon'] !!}
                                    </svg>
                                @endif
                            </span>
                            <div class="min-w-0 max-w-full flex-1 overflow-hidden">
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <p class="min-w-0 flex-1 truncate text-[15px] font-semibold text-[#111827]">{{ $conversation->customer_name }}</p>
                                    <span class="shrink-0 text-xs font-semibold {{ $unreadCount > 0 ? 'text-[#10B981]' : 'text-[#6B7280]' }}">{{ $conversation->last_message_at?->format('H:i') }}</span>
                                </div>
                                <div class="mt-1 flex min-w-0 items-center justify-between gap-2">
                                    <p class="min-w-0 flex-1 truncate text-sm text-[#6B7280]">
                                        @if ($conversation->status === 'Waiting')
                                            <span class="mr-1 inline-flex text-[#2563EB]">
                                                <svg aria-hidden="true" viewBox="0 0 18 10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-5">
                                                    <path d="M1 5.5 3.8 8 9 1"></path>
                                                    <path d="M8 7.5 10.2 8 17 1"></path>
                                                </svg>
                                            </span>
                                        @endif
                                        @if ($conversation->channel === 'Gmail' && ($conversation->latestMessage?->metadata['subject'] ?? null))
                                            {{ $conversation->latestMessage->metadata['subject'] }}
                                        @else
                                            {{ $conversation->latestMessage?->body ?? 'No messages yet.' }}
                                        @endif
                                    </p>
                                    @if ($unreadCount > 0)
                                        <span class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#10B981] px-1.5 text-xs font-bold text-white" aria-label="{{ $unreadCount }} unread message">{{ $unreadCount }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $statusMeta['class'] }}" title="{{ $statusMeta['label'] }}" aria-label="{{ $statusMeta['label'] }}">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                            {!! $statusMeta['icon'] !!}
                                        </svg>
                                    </span>
                                    <span class="hidden items-center gap-1 rounded-full bg-[#EEF0F3] px-2 py-0.5 text-xs font-semibold text-[#6B7280] sm:inline-flex" title="{{ $intent }}">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                            <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"></path>
                                            <path d="M7.5 7.5h.01"></path>
                                        </svg>
                                        {{ $intent }}
                                    </span>
                                    @if ($latestReplyDisabled)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#EEF0F3] px-2 py-0.5 text-xs font-semibold text-[#6B7280]" title="Replies disabled">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                                <path d="M22 2 11 13"></path>
                                                <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                                <path d="m2 2 20 20"></path>
                                            </svg>
                                            No reply
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="p-6 text-sm text-[#6B7280]">No conversations in this state.</p>
                @endforelse
                @if ($hasMoreConversations)
                    <div class="px-5 py-4 text-center text-xs font-semibold text-[#6B7280]">Showing latest {{ $conversationPageSize }} conversations. Narrow the search or filters to find older threads.</div>
                @endif
            </div>
        </aside>

        <section class="{{ $conversationIsOpen ? 'flex' : 'hidden' }} h-full min-h-0 flex-col bg-[#F5F6F8] lg:flex">
            @if ($selectedConversation)
                @php
                    $statusClass = match ($selectedConversation->status) {
                        'AI Handling' => 'text-[#047857]',
                        'Waiting' => 'text-[#B45309]',
                        'Needs Human' => 'text-[#BE185D]',
                        default => 'text-[#6B7280]',
                    };
                    $replyDisabled = $selectedConversation->replyDisabled();
                    $replyDisabledReason = $selectedConversation->replyDisabledReason() ?? 'Automated or not replyable email';
                    $isGmailThread = $selectedConversation->channel === 'Gmail';
                    $gmailThreadMessageCount = $isGmailThread ? $selectedConversation->messages->where('metadata.source', 'gmail')->count() : 0;
                    $selectedAvatarUrl = $selectedConversation->customer?->avatarUrl();
                    $selectedChannel = \App\Support\InboxUi::channelMeta($selectedConversation->channel);
                @endphp
                <div class="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-[#E5E7EB] bg-white px-3 py-3 sm:px-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <a href="{{ route('dashboard.inbox', $filterQuery) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#374151] hover:bg-[#F5F6F8] lg:hidden" aria-label="Back to messages">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M15 18 9 12l6-6"></path>
                            </svg>
                        </a>
                        <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#EEF0F3] text-sm font-bold text-[#111827]">
                            @if ($selectedAvatarUrl)
                                <img src="{{ $selectedAvatarUrl }}" alt="" class="h-full w-full object-cover">
                                <span class="absolute bottom-0 right-0 flex h-5 w-5 items-center justify-center rounded-md border border-white {{ $selectedChannel['class'] }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3 w-3">
                                        {!! $selectedChannel['icon'] !!}
                                    </svg>
                                </span>
                            @else
                                {{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}
                            @endif
                        </span>
                        <button type="button" x-on:click="profileOpen = true" class="min-w-0 text-left xl:pointer-events-none" aria-label="Open customer profile">
                            <h3 class="truncate text-base font-semibold text-[#111827]">{{ $selectedConversation->customer_name }}</h3>
                            <p class="truncate text-xs font-semibold {{ $statusClass }}">
                                {{ $selectedConversation->status }} / {{ $selectedConversation->channel }}
                                @if ($isGmailThread && $gmailThreadMessageCount > 1)
                                    <span class="text-[#6B7280]">/ {{ $gmailThreadMessageCount }} emails</span>
                                @endif
                            </p>
                        </button>
                    </div>
                </div>

                <div data-chat-scroll class="min-h-0 flex-1 space-y-2.5 overflow-x-hidden overflow-y-auto bg-[#F5F6F8] p-3 sm:p-5">
                    @foreach ($selectedConversation->messages as $message)
                        @php
                            $isGmailMessage = $selectedConversation->channel === 'Gmail' || ($message->metadata['source'] ?? null) === 'gmail';
                            $gmailSubject = $message->metadata['subject'] ?? null;
                            $gmailFrom = $message->metadata['from_email'] ?? $selectedConversation->customer_external_id;
                            $gmailTo = $message->metadata['to_email'] ?? null;
                            $gmailReplyDisabled = (bool) (($message->metadata['reply_disabled'] ?? false) || preg_match('/(^|[._+-])(no-?reply|do-?not-?reply|donotreply)([._+-]|@)/i', strtolower((string) $gmailFrom)));
                            $gmailReplyDisabledReason = $message->metadata['reply_disabled_reason'] ?? 'Automated sender';
                            $senderLabel = match (true) {
                                $message->direction === 'incoming' => $selectedConversation->customer_name,
                                $message->sender_type === 'ai' => 'Assistant',
                                $message->sender_type === 'human' => auth()->user()?->name ?? 'Team',
                                default => ucfirst($message->sender_type),
                            };
                            $gmailSenderLabel = $message->direction === 'incoming'
                                ? $selectedConversation->customer_name
                                : $senderLabel;
                            $messageBody = $message->body;

                            if ($isGmailMessage && $gmailSubject) {
                                $messageBody = preg_replace('/^\s*Subject:\s*'.preg_quote($gmailSubject, '/').'\s*/i', '', $messageBody) ?? $messageBody;
                                $messageBody = preg_replace('/^\s*Subject:\s*[^\r\n]+(?:\r?\n){1,2}/i', '', $messageBody) ?? $messageBody;
                                $messageBody = preg_replace('/\s*(?:<!doctype|<html|<head|<style|<body)\b.*$/is', '', $messageBody) ?? $messageBody;
                            }

                            if ($isGmailMessage) {
                                $messageBody = str_replace(["\r\n", "\r"], "\n", $messageBody);
                                $messageBody = preg_replace('/\nOn .+?wrote:\n(?:>.*\n?)+/is', "\n", $messageBody) ?? $messageBody;
                                $messageBody = preg_replace('/^\s*>.*(?:\n|$)/m', '', $messageBody) ?? $messageBody;
                                $messageBody = preg_replace('/[ \t]+/', ' ', $messageBody) ?? $messageBody;
                                $messageBody = preg_replace('/[ \t]*\n[ \t]*/', "\n", $messageBody) ?? $messageBody;
                                $messageBody = preg_replace("/\n{3,}/", "\n\n", $messageBody) ?? $messageBody;
                                $messageBody = implode("\n", array_map('trim', explode("\n", $messageBody)));
                            }

                            $messageBody = trim($messageBody);
                            $replyContext = $message->metadata['reply_to'] ?? null;
                        @endphp
                        @php
                            $hasImageAttachment = $message->attachments->contains(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'image/'));
                            $hasVideoAttachment = $message->attachments->contains(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'video/'));
                            $hasAudioAttachment = $message->attachments->contains(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'audio/'));
                            $attachmentOnlyMessage = $messageBody === '' && $message->attachments->isNotEmpty();
                            $mediaOnlyPlaceholder = match ($messageBody) {
                                '[Photo]' => $hasImageAttachment,
                                '[Video]' => $hasVideoAttachment,
                                '[Voice note]', '[Audio]' => $hasAudioAttachment,
                                default => $attachmentOnlyMessage && ($hasImageAttachment || $hasVideoAttachment || $hasAudioAttachment),
                            };
                            $mediaOnlyAudio = ! $isGmailMessage && $mediaOnlyPlaceholder && $hasAudioAttachment && ! ($hasImageAttachment || $hasVideoAttachment);
                            $mediaOnlyVisual = ! $isGmailMessage && $mediaOnlyPlaceholder && ($hasImageAttachment || $hasVideoAttachment);
                            $mediaOnlyAttachment = $mediaOnlyVisual || $mediaOnlyAudio;
                            $messageSummary = $messageBody !== ''
                                ? \Illuminate\Support\Str::limit(strip_tags($messageBody), 90)
                                : ($message->attachments->isNotEmpty() ? 'Attachment' : 'Message');
                        @endphp
                        <div
                            class="relative flex w-full min-w-0 max-w-full overflow-hidden {{ $message->direction === 'outgoing' ? 'justify-end' : 'justify-start' }}"
                            style="touch-action: pan-y;"
                            x-data="window.swipeReplyMessage({
                                id: {{ $message->id }},
                                sender: @js($senderLabel),
                                body: @js($messageSummary)
                            })"
                            x-on:pointerdown="begin($event)"
                            x-on:pointermove="move($event)"
                            x-on:pointerup="end"
                            x-on:pointercancel="end"
                        >
                            <div class="pointer-events-none absolute top-1/2 z-0 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-[#2563EB] opacity-0 shadow-sm transition {{ $message->direction === 'outgoing' ? 'right-2' : 'left-2' }}" x-bind:class="Math.abs(offsetX) > 18 ? 'opacity-100' : 'opacity-0'">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="m9 14-4-4 4-4"></path>
                                    <path d="M5 10h10a4 4 0 0 1 4 4v1"></path>
                                </svg>
                            </div>
                            <div class="relative z-10 min-w-0 {{ $isGmailMessage ? 'max-w-[94%] sm:max-w-[82%]' : ($mediaOnlyVisual ? 'max-w-[76vw] sm:max-w-[22rem]' : ($mediaOnlyAudio ? 'w-[16.5rem] max-w-[86%]' : 'max-w-[84%] sm:max-w-[68%]')) }} transition-transform duration-150 ease-out" x-bind:style="`transform: translateX(${offsetX}px)`">
                                <div class="rounded-2xl border text-sm {{ $mediaOnlyVisual ? 'overflow-hidden px-1 pb-1 pt-1' : ($mediaOnlyAudio ? 'px-2.5 py-2' : 'px-3 py-2') }} {{ $mediaOnlyAttachment ? 'shadow-none' : 'shadow-sm' }} {{ $message->direction === 'outgoing' ? 'border-[#BFDBFE] bg-[#EFF6FF] text-[#111827]' : 'border-[#E5E7EB] bg-white text-[#111827]' }}">
                                    @if ($replyContext)
                                        <div class="mb-2 border-l-2 border-[#2563EB] bg-white/60 px-2 py-1.5">
                                            <p class="truncate text-xs font-bold text-[#6B7280]">{{ $replyContext['sender'] ?? 'Message' }}</p>
                                            <p class="truncate text-xs text-[#6B7280]">{{ $replyContext['body'] ?? 'Attachment' }}</p>
                                        </div>
                                    @endif
                                    @if ($isGmailMessage)
                                        <div class="mb-3 border-b border-[#E5E7EB] pb-3">
                                        <div class="flex items-center gap-2 text-xs font-bold text-[#6B7280]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#ea4335]">
                                                <path d="M4 6h16v12H4z"></path>
                                                <path d="m4 7 8 6 8-6"></path>
                                            </svg>
                                            {{ $gmailSenderLabel }}
                                            @if ($gmailThreadMessageCount > 1)
                                                <span class="rounded-full bg-[#EEF0F3] px-2 py-0.5 text-[10px] text-[#6B7280]">Thread {{ $loop->iteration }} of {{ $gmailThreadMessageCount }}</span>
                                            @endif
                                            @if ($gmailReplyDisabled)
                                                <span class="rounded-full bg-[#EEF0F3] px-2 py-0.5 text-[10px] text-[#6B7280]">{{ $gmailReplyDisabledReason }}</span>
                                            @endif
                                        </div>
                                        @if ($gmailSubject)
                                            <h4 class="mt-2 break-words text-base font-bold leading-6 text-[#111827]">{{ $gmailSubject }}</h4>
                                        @endif
                                        <div class="mt-2 space-y-1 text-xs font-semibold text-[#6B7280]">
                                            <p class="break-all"><span class="text-[#6B7280]">From</span> {{ $gmailFrom }}</p>
                                            @if ($gmailTo)
                                                <p class="break-all"><span class="text-[#6B7280]">To</span> {{ $gmailTo }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="whitespace-pre-line break-words leading-6 text-[#111827]">{!! \App\Support\MessageText::linkify($messageBody !== '' ? $messageBody : '(empty email)') !!}</div>
                                @else
                                    @unless ($mediaOnlyPlaceholder)
                                        <p class="mb-1 text-xs font-bold {{ $message->sender_type === 'ai' ? 'text-[#047857]' : 'text-[#6B7280]' }}">{{ $senderLabel }}</p>
                                        <div class="whitespace-pre-line break-words leading-6">{!! \App\Support\MessageText::linkify($messageBody) !!}</div>
                                    @endunless
                                @endif
                                @if ($message->attachments->isNotEmpty())
                                    <div class="{{ $mediaOnlyAttachment ? 'mt-0' : 'mt-3' }} space-y-2">
                                        @foreach ($message->attachments as $attachment)
                                            @php
                                                $isPdf = $attachment->mime_type === 'application/pdf';
                                                $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
                                                $isVoiceNote = ($attachment->metadata['media_type'] ?? null) === 'voice' || str_starts_with($attachment->filename, 'voice-note-');
                                                $isAudio = $isVoiceNote || str_starts_with((string) $attachment->mime_type, 'audio/');
                                                $isVideo = ! $isVoiceNote && str_starts_with((string) $attachment->mime_type, 'video/');
                                                $hasInlinePreview = $isImage || $isAudio || $isVideo;
                                                $inlineUrl = route('dashboard.attachments.download', ['attachment' => $attachment, 'inline' => 1]);
                                                $size = (int) ($attachment->size ?? 0);
                                                if ($size >= 1048576) {
                                                    $sizeLabel = round($size / 1048576, 1).' MB';
                                                } elseif ($size >= 1024) {
                                                    $sizeLabel = round($size / 1024, 1).' KB';
                                                } elseif ($size > 0) {
                                                    $sizeLabel = $size.' B';
                                                } else {
                                                    $sizeLabel = 'Unknown size';
                                                }
                                            @endphp
                                            @if ($isImage)
                                                <div>
                                                    <button type="button" x-on:click.stop="openMedia({ type: 'image', src: @js($inlineUrl), alt: @js($attachment->filename) })" data-media-frame class="media-preview-frame media-preview-frame--image block overflow-hidden rounded-xl bg-[#EEF0F3] text-left transition hover:opacity-95" aria-label="Open image preview">
                                                        <img src="{{ $inlineUrl }}" alt="{{ $attachment->filename }}" loading="lazy" decoding="async" x-on:load="$event.target.closest('[data-media-frame]')?.classList.add('media-loaded')" x-on:error="markMediaFailed($event, 'Image unavailable')" class="block max-h-[18rem] max-w-full rounded-xl object-contain">
                                                    </button>
                                                </div>
                                            @elseif ($isVideo)
                                                <div data-media-frame class="media-preview-frame media-preview-frame--video overflow-hidden rounded-xl bg-[#111827]">
                                                    <button type="button" x-on:click.stop="openMedia({ type: 'video', src: @js($inlineUrl), alt: @js($attachment->filename) })" class="group relative block aspect-[4/5] max-h-[20rem] w-auto max-w-full overflow-hidden rounded-xl bg-[#111827] text-white" aria-label="Open video">
                                                        <video playsinline preload="metadata" src="{{ $inlineUrl }}" x-on:loadedmetadata="$event.target.closest('[data-media-frame]')?.classList.add('media-loaded')" x-on:error="markMediaFailed($event, 'Video unavailable')" class="h-full max-h-[20rem] w-auto max-w-full object-contain"></video>
                                                        <span class="absolute inset-0 flex items-center justify-center bg-black/10 transition group-hover:bg-black/20">
                                                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/90 text-[#111827] shadow-lg">
                                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-7 w-7">
                                                                    <path d="M8 5.8c0-.8.9-1.3 1.6-.9l8.2 5.2c.7.4.7 1.4 0 1.8l-8.2 5.2c-.7.4-1.6-.1-1.6-.9V5.8Z"></path>
                                                                </svg>
                                                            </span>
                                                        </span>
                                                    </button>
                                                </div>
                                            @elseif ($isAudio)
                                                <div
                                                    x-data="window.voiceNotePlayer(@js($inlineUrl))"
                                                    x-on:destroy.window="destroy"
                                                    class="w-full rounded-xl bg-transparent"
                                                >
                                                    <div class="flex items-center gap-2.5">
                                                        <button type="button" x-on:click="toggle" x-bind:class="ready ? 'bg-[#2563EB] text-white hover:bg-[#1d4ed8]' : 'bg-[#EEF0F3] text-[#9CA3AF]'" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full shadow-sm transition" aria-label="Play voice note">
                                                            <svg x-show="! playing" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                                                <path d="M8 5.8c0-.8.9-1.3 1.6-.9l8.2 5.2c.7.4.7 1.4 0 1.8l-8.2 5.2c-.7.4-1.6-.1-1.6-.9V5.8Z"></path>
                                                            </svg>
                                                            <svg x-cloak x-show="playing" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                                                <path d="M7 5h3v14H7zM14 5h3v14h-3z"></path>
                                                            </svg>
                                                        </button>
                                                        <div class="min-w-0 flex-1">
                                                            <div x-ref="waveform" class="h-8 w-full min-w-0"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-0.5 flex justify-end">
                                                        <span class="text-[11px] font-semibold tabular-nums text-[#6B7280]" x-text="displayTime"></span>
                                                    </div>
                                                </div>
                                            @endif
                                            @unless ($hasInlinePreview)
                                            <a href="{{ route('dashboard.attachments.download', $attachment) }}" class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3 transition hover:bg-[#EEF0F3]">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $isPdf ? 'bg-pink-50 text-[#BE185D]' : ($isImage ? 'bg-[#EFF6FF] text-[#2563EB]' : ($isVideo ? 'bg-[#EEF2FF] text-[#4F46E5]' : ($isAudio ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#EEF0F3] text-[#374151]'))) }}">
                                                    @if ($isPdf)
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                            <path d="M14 2v6h6"></path>
                                                            <path d="M8 16h8"></path>
                                                            <path d="M8 12h3"></path>
                                                        </svg>
                                                    @elseif ($isImage)
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                                            <circle cx="8.5" cy="10" r="1.5"></circle>
                                                            <path d="m21 15-4.5-4.5L9 18"></path>
                                                        </svg>
                                                    @elseif ($isAudio)
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                                            <path d="M19 11a7 7 0 0 1-14 0"></path>
                                                            <path d="M12 18v3"></path>
                                                        </svg>
                                                    @elseif ($isVideo)
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M15 10.5 20 7v10l-5-3.5"></path>
                                                            <rect x="3" y="6" width="12" height="12" rx="2"></rect>
                                                        </svg>
                                                    @else
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                            <path d="M14 2v6h6"></path>
                                                        </svg>
                                                    @endif
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-bold text-[#111827]">{{ $attachment->filename }}</span>
                                                    <span class="mt-0.5 block truncate text-xs font-semibold text-[#6B7280]">{{ $attachment->mime_type ?: 'File' }} / {{ $sizeLabel }}</span>
                                                </span>
                                                <span class="shrink-0 text-[#6B7280]">
                                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <path d="M7 10l5 5 5-5"></path>
                                                        <path d="M12 15V3"></path>
                                                    </svg>
                                                </span>
                                            </a>
                                            @endunless
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="mt-1 flex justify-end gap-1 pr-1 text-[11px] text-[#6B7280]">
                                <span>{{ $message->created_at?->format('H:i') }}</span>
                                @if ($message->direction === 'outgoing')
                                    <span class="inline-flex text-[#2563EB]">
                                        <svg aria-hidden="true" viewBox="0 0 18 10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-5">
                                            <path d="M1 5.5 3.8 8 9 1"></path>
                                            <path d="M8 7.5 10.2 8 17 1"></path>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                            </div>
                        </div>
                    @endforeach
                    <div data-chat-bottom class="h-1"></div>
                </div>

                <div class="chat-composer shrink-0 border-t border-[#E5E7EB] bg-white">
                    @if ($replyDisabled)
                        <div class="flex flex-col gap-3 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF0F3] text-[#6B7280]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M22 2 11 13"></path>
                                        <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                        <path d="m2 2 20 20"></path>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-[#111827]">Replies disabled</p>
                                    <p class="mt-1 text-xs leading-5 text-[#6B7280]">This email thread looks like {{ strtolower($replyDisabledReason) }}. Keep it as a record or mark it reviewed.</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('dashboard.inbox.close', $selectedConversation) }}" class="shrink-0">
                                @csrf
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] sm:w-auto">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                    Mark reviewed
                                </button>
                            </form>
                        </div>
                    @else
                        @php
                            $voiceNotesDisabled = $selectedConversation->channel === 'Gmail';
                        @endphp
                        <form method="POST" action="{{ route('dashboard.inbox.reply', $selectedConversation) }}" enctype="multipart/form-data" class="space-y-2" data-human-on-submit="true" x-data="window.inboxComposer(@js($selectedConversation->ai_mode === 'human'))" x-on:submit="submitAfterRecording($event)">
                            @csrf
                            <input type="hidden" name="reply_to_message_id" x-bind:value="replyTo?.id || ''">
                            <div x-show="replyTo" x-cloak x-transition class="rounded-xl border border-[#E5E7EB] bg-white px-3 py-2 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF] text-[#2563EB]">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="m9 14-4-4 4-4"></path>
                                            <path d="M5 10h10a4 4 0 0 1 4 4v1"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 border-l-2 border-[#2563EB] pl-3">
                                        <p class="truncate text-xs font-bold text-[#111827]" x-text="replyTo?.sender || 'Message'"></p>
                                        <p class="truncate text-xs text-[#6B7280]" x-text="replyTo?.body || 'Attachment'"></p>
                                    </div>
                                    <button type="button" x-on:click="clearReply" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#6B7280] transition hover:bg-[#F5F6F8] hover:text-[#111827]" aria-label="Cancel reply">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" class="h-4 w-4">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div x-show="recording || voiceNoteReady || recordError" x-cloak x-transition class="px-1">
                                <div x-show="recording" class="flex items-center gap-3 rounded-xl border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 shadow-sm">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#DC2626] shadow-sm">
                                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-[#DC2626]"></span>
                                    </span>
                                    <div x-ref="recordWaveform" class="h-8 min-w-0 flex-1"></div>
                                    <span class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-[#DC2626]" x-text="recordElapsed"></span>
                                    <button type="button" x-on:click="stopRecorder" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#DC2626] shadow-sm transition hover:bg-[#FEE2E2]" aria-label="Stop voice note" title="Stop voice note">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5">
                                            <rect x="7" y="7" width="10" height="10" rx="2"></rect>
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="! recording && voiceNoteReady" class="flex items-center gap-3 rounded-xl border border-[#BFDBFE] bg-[#EFF6FF] px-3 py-2 shadow-sm">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#2563EB] shadow-sm">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                            <path d="M19 11a7 7 0 0 1-14 0"></path>
                                            <path d="M12 18v3"></path>
                                        </svg>
                                    </span>
                                    <div class="flex min-w-0 flex-1 items-center gap-1.5 text-[#2563EB]" aria-hidden="true">
                                        @for ($i = 0; $i < 24; $i++)
                                            <span class="w-0.5 rounded-full bg-current opacity-70" style="height: {{ [8, 12, 16, 10, 20, 14, 24, 12][$i % 8] }}px"></span>
                                        @endfor
                                    </div>
                                    <span class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-[#2563EB]" x-text="recordedVoiceElapsed"></span>
                                    <button type="button" x-on:click="clearVoiceNote" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#6B7280] shadow-sm transition hover:text-[#DC2626]" aria-label="Discard voice note" title="Discard voice note">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="recordError" class="rounded-xl border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 text-xs font-semibold text-[#DC2626]" x-text="recordError"></div>
                            </div>
                            <div x-show="mediaFileCount > 0" x-cloak x-transition class="rounded-xl border border-[#E5E7EB] bg-white px-2 py-2 shadow-sm">
                                <input x-ref="imageInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-images-{{ $selectedConversation->id }}" multiple accept="image/*,video/*">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex min-h-12 min-w-0 flex-1 items-center gap-1 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] px-2 sm:gap-2 sm:px-3">
                                    <div class="relative shrink-0" x-on:click.outside="emojiOpen = false">
                                        <button type="button" x-on:click="emojiOpen = ! emojiOpen" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Add emoji" title="Add emoji">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M8.5 10h.01"></path>
                                                <path d="M15.5 10h.01"></path>
                                                <path d="M8 14.5c1.1 1 2.4 1.5 4 1.5s2.9-.5 4-1.5"></path>
                                            </svg>
                                        </button>
                                        <div x-cloak x-show="emojiOpen" x-transition class="absolute bottom-11 left-0 z-[80] max-h-72 w-72 overflow-y-auto rounded-xl border border-[#E5E7EB] bg-white p-3 shadow-xl shadow-slate-900/10">
                                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Emoji</p>
                                            <div class="grid grid-cols-8 gap-1">
                                                @foreach (['&#128512;', '&#128513;', '&#128514;', '&#128522;', '&#128525;', '&#128526;', '&#129392;', '&#128578;', '&#128077;', '&#128079;', '&#128591;', '&#128170;', '&#128076;', '&#9996;', '&#128075;', '&#129309;', '&#9989;', '&#128204;', '&#128293;', '&#127881;', '&#128153;', '&#128154;', '&#128155;', '&#10084;&#65039;', '&#128172;', '&#128197;', '&#128276;', '&#128269;', '&#128640;', '&#128161;', '&#9200;', '&#128176;', '&#128221;', '&#128206;', '&#128247;', '&#127908;', '&#128226;', '&#9888;', '&#128721;', '&#11088;'] as $emoji)
                                                    <button type="button" x-on:click="insertEmoji($event.currentTarget.textContent.trim())" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg transition hover:bg-[#EFF6FF]" aria-label="Insert emoji">{!! $emoji !!}</button>
                                                @endforeach
                                            </div>
                                            <button type="button" x-on:click="emojiOpen = false" class="mt-3 w-full rounded-lg border border-[#E5E7EB] bg-[#F5F6F8] px-3 py-2 text-xs font-bold text-[#374151] transition hover:bg-white">Close</button>
                                        </div>
                                    </div>
                                    <textarea x-ref="messageInput" x-on:input="updateTyping" name="body" rows="1" class="min-w-[5rem] max-h-28 flex-1 resize-none border-0 bg-transparent py-3 text-sm text-[#111827] placeholder:text-[#6B7280] focus:ring-0" placeholder="Message"></textarea>
                                    <input x-ref="fileInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-attachments-{{ $selectedConversation->id }}" multiple class="hidden">
                                    @unless ($voiceNotesDisabled)
                                        <input x-ref="audioInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-audio-{{ $selectedConversation->id }}" accept="audio/*" class="hidden">
                                    @endunless
                                    <div x-bind:class="composerHasText ? 'w-0 max-w-0 scale-95 opacity-0 pointer-events-none overflow-hidden' : 'w-auto max-w-[15rem] scale-100 opacity-100'" class="flex shrink-0 items-center gap-1 transition-all duration-200 ease-out sm:gap-2">
                                        <label for="message-attachments-{{ $selectedConversation->id }}" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Attach file" title="Attach file">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 1 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                            </svg>
                                        </label>
                                        <button type="button" x-on:click="browseMedia" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Upload media" title="Upload image or video">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <rect x="3" y="5" width="18" height="14" rx="2.5"></rect>
                                                <circle cx="8.5" cy="10" r="1.5"></circle>
                                                <path d="m21 15-4.5-4.5L8 19"></path>
                                            </svg>
                                        </button>
                                        @if ($voiceNotesDisabled)
                                            <span class="relative inline-flex h-8 w-8 shrink-0 cursor-not-allowed items-center justify-center rounded-md text-[#9CA3AF] sm:h-9 sm:w-9" aria-label="Voice notes are disabled for email" title="Voice notes are disabled for email">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                    <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                                    <path d="M19 11a7 7 0 0 1-14 0"></path>
                                                    <path d="M12 18v3"></path>
                                                </svg>
                                                <span class="absolute h-px w-6 rotate-45 bg-[#9CA3AF]"></span>
                                            </span>
                                        @else
                                            <button type="button" x-on:click="toggleRecorder" x-bind:class="recording ? 'bg-[#FEE2E2] text-[#DC2626]' : 'text-[#6B7280] hover:bg-white hover:text-[#2563EB]'" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition sm:h-9 sm:w-9" aria-label="Record voice note" title="Record voice note">
                                                <svg x-show="! recording" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                    <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                                    <path d="M19 11a7 7 0 0 1-14 0"></path>
                                                    <path d="M12 18v3"></path>
                                                </svg>
                                                <svg x-cloak x-show="recording" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <rect x="7" y="7" width="10" height="10" rx="2"></rect>
                                                </svg>
                                            </button>
                                        @endif
                                        <button x-show="! automationPaused" x-on:click="automationPaused = true" data-instant-action="true" formaction="{{ route('dashboard.inbox.take-over', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#EFF6FF] text-[#2563EB] transition hover:bg-[#DBEAFE] sm:h-9 sm:w-9" aria-label="Automation active. Pause automation." title="Automation active">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                    <path d="M10 5v14"></path>
                                                    <path d="M14 5v14"></path>
                                                </svg>
                                        </button>
                                        <button x-cloak x-show="automationPaused" x-on:click="automationPaused = false" data-instant-action="true" formaction="{{ route('dashboard.inbox.resume-ai', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[#9CA3AF] transition hover:bg-[#EFF6FF] hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Automation paused. Resume automation." title="Automation paused">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M7.5 5.7c0-.9 1-1.4 1.8-.9l8.6 5.3c.7.4.7 1.4 0 1.8l-8.6 5.3c-.8.5-1.8-.1-1.8-.9V5.7Z"></path>
                                                </svg>
                                        </button>
                                    </div>
                                </div>
                                <button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#2563EB] text-white shadow-sm transition hover:bg-[#1d4ed8]" aria-label="Send message">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                        <path d="M22 2 11 13"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 px-1">
                                <template x-for="file in genericFiles" :key="file.name">
                                    <span x-show="genericFiles.length > 0" x-cloak class="inline-flex max-w-full items-center gap-2 rounded-lg bg-[#EEF0F3] px-3 py-2 text-xs font-semibold text-[#6B7280]">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <path d="M14 2v6h6"></path>
                                        </svg>
                                        <span class="truncate" x-text="file.name"></span>
                                    </span>
                                </template>
                            </div>
                        </form>
                    @endif
                </div>
            @else
                <div class="flex h-full items-center justify-center bg-[#F5F6F8] p-8 text-sm text-[#6B7280]">Select a conversation.</div>
            @endif
        </section>

        <aside class="hidden h-full min-h-0 overflow-y-auto border-l border-[#E5E7EB] bg-white p-5 xl:block">
            @if ($selectedConversation)
                <h3 class="font-bold text-[#111827]">Customer profile</h3>
                <div class="mt-5 flex items-center gap-3 rounded-xl bg-[#F5F6F8] p-4">
                    <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-sm font-bold text-[#111827]">
                        @if ($selectedAvatarUrl)
                            <img src="{{ $selectedAvatarUrl }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-[#111827]">{{ $selectedConversation->customer_name }}</p>
                        <p class="truncate text-xs font-semibold text-[#6B7280]">{{ $selectedConversation->channel }}</p>
                    </div>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Name</dt>
                        <dd class="mt-1 font-bold text-[#111827]">{{ $selectedConversation->customer_name }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Channel</dt>
                        <dd class="mt-1 font-bold text-[#111827]">{{ $selectedConversation->channel }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">{{ $customerIdentityLabel }}</dt>
                        <dd class="mt-1 font-mono text-xs font-semibold text-[#374151]">{{ $selectedConversation->customer_external_id }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Mode</dt>
                        <dd class="mt-1 font-bold text-[#111827]">{{ ucfirst($selectedConversation->ai_mode) }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Detected intent</dt>
                        <dd class="mt-2 inline-flex rounded-full bg-[#ECFDF5] px-3 py-1 text-xs font-bold text-[#047857]">{{ $selectedConversation->getAttribute('detected_intent') }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Notes</dt>
                        <dd class="mt-1 leading-6 text-[#374151]">{{ $selectedConversation->customer?->notes ?? 'No notes yet.' }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#F5F6F8] p-4">
                        <dt class="font-semibold text-[#6B7280]">Tags</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#374151]">{{ $selectedConversation->status }}</span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#374151]">{{ $selectedConversation->channel }}</span>
                        </dd>
                    </div>
                </dl>
            @endif
        </aside>

        <div
            x-cloak
            x-show="mediaViewer.open"
            x-transition.opacity
            x-on:keydown.escape.window="closeMedia"
            x-on:click.self="closeMedia"
            class="fixed inset-0 z-[140] flex items-center justify-center bg-[#0F1115]/90 p-3 sm:p-5"
            role="dialog"
            aria-modal="true"
        >
            <button type="button" x-on:click="closeMedia" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-[#111827] shadow-lg transition hover:bg-white" aria-label="Close media preview">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>

            <div
                class="flex max-h-[88dvh] w-full max-w-[96vw] items-center justify-center sm:max-w-3xl"
                x-on:pointerdown="startMediaDrag($event)"
                x-on:pointermove="moveMediaDrag($event)"
                x-on:pointerup="endMediaDrag"
                x-on:pointercancel="endMediaDrag"
                x-bind:style="`transform: translateY(${dragY}px)`"
            >
                <template x-if="mediaViewer.type === 'image'">
                    <img x-bind:src="mediaViewer.src" x-bind:alt="mediaViewer.alt" class="max-h-[88dvh] max-w-full rounded-2xl object-contain shadow-2xl">
                </template>
                <template x-if="mediaViewer.type === 'video'">
                    <video x-ref="mediaVideo" x-bind:src="mediaViewer.src" controls playsinline preload="metadata" class="h-[82dvh] max-h-[82dvh] w-auto max-w-[96vw] rounded-2xl bg-[#111827] object-contain shadow-2xl sm:h-[84dvh]"></video>
                </template>
            </div>
        </div>

        @if ($selectedConversation)
            <div
                x-cloak
                class="pointer-events-none fixed inset-0 z-[80] xl:hidden"
                x-bind:class="profileOpen ? 'pointer-events-auto' : ''"
                x-on:keydown.escape.window="profileOpen = false"
            >
                <div
                    x-show="profileOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-slate-950/35 backdrop-blur-sm"
                    x-on:click="profileOpen = false"
                    aria-hidden="true"
                ></div>
                <section
                    x-show="profileOpen"
                    x-transition:enter="transition ease-out duration-250"
                    x-transition:enter-start="translate-y-8 scale-[0.98] opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-8 scale-[0.98] opacity-0"
                    class="absolute inset-x-3 bottom-3 max-h-[82vh] overflow-y-auto rounded-2xl border border-[#E5E7EB] bg-white shadow-2xl shadow-slate-900/20"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Customer profile"
                    x-on:click.stop
                >
                    <div class="flex items-center justify-between border-b border-[#E5E7EB] px-4 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#EEF0F3] text-sm font-bold text-[#111827]">
                                @if ($selectedAvatarUrl)
                                    <img src="{{ $selectedAvatarUrl }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-[#111827]">{{ $selectedConversation->customer_name }}</h3>
                                <p class="mt-0.5 truncate text-xs font-bold {{ $statusClass }}">{{ $selectedConversation->status }} / {{ $selectedConversation->channel }}</p>
                            </div>
                        </div>
                        <button type="button" x-on:click="profileOpen = false" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F5F6F8] text-[#374151]" aria-label="Close customer profile">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <dl class="grid gap-3 p-4 text-sm">
                        <div class="rounded-xl bg-[#F5F6F8] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Channel</dt>
                            <dd class="mt-1 font-bold text-[#111827]">{{ $selectedConversation->channel }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Mode</dt>
                            <dd class="mt-1 font-bold text-[#111827]">{{ ucfirst($selectedConversation->ai_mode) }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Detected intent</dt>
                            <dd class="mt-2 inline-flex rounded-full bg-[#ECFDF5] px-3 py-1 text-xs font-bold text-[#047857]">{{ $selectedConversation->getAttribute('detected_intent') }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">{{ $customerIdentityLabel }}</dt>
                            <dd class="mt-1 break-all font-mono text-xs font-semibold text-[#374151]">{{ $selectedConversation->customer_external_id }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#F5F6F8] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Notes</dt>
                            <dd class="mt-2 leading-6 text-[#374151]">{{ $selectedConversation->customer?->notes ?? 'No notes yet.' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        @endif
    </div>
</x-app-layout>
