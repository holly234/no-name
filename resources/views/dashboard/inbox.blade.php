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
            'exact_date' => $activeExactDate ?: null,
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
        $advancedFiltersActive = $activeDate !== 'all' || $activeExactDate !== '' || $activeTime !== 'all' || $activeSort !== 'newest';
        $compactStateLabels = [
            'All' => 'Inbox',
            \App\Models\Conversation::STATE_NEEDS_HUMAN => 'Needs reply',
            \App\Models\Conversation::STATE_AI_HANDLING => 'AI',
            \App\Models\Conversation::STATE_WAITING => 'Scheduled',
            \App\Models\Conversation::STATE_INFORMATIONAL => 'Info',
            \App\Models\Conversation::STATE_CLOSED => 'Done',
        ];
        $channelIconTone = [
            'All' => 'text-[#8b8b91]',
            'Instagram' => 'text-[#dd2a7b]',
            'WhatsApp' => 'text-[#8b8b91]',
            'Facebook' => 'text-[#1877f2]',
            'Gmail' => 'text-[#ea4335]',
            'Telegram' => 'text-[#229ED9]',
        ];
        $activeChannelLabel = $activeChannel === 'All'
            ? 'All channels'
            : data_get($channelMeta, $activeChannel.'.label', $activeChannel);
        $activeStateLabel = $compactStateLabels[$activeState] ?? data_get($filterMeta, $activeState.'.label', $activeState);
    @endphp

    <div x-data="window.inboxPage()" data-inbox-version="{{ $inboxVersion }}" class="grid h-full min-h-0 w-full min-w-0 max-w-full grid-cols-[minmax(0,1fr)] overflow-hidden bg-[#0e0e10] text-[#8b8b91] lg:grid-cols-[410px_minmax(0,1fr)] xl:grid-cols-[410px_minmax(0,1fr)_320px]">
        <aside class="{{ $conversationIsOpen ? 'hidden' : 'flex' }} h-full min-h-0 w-full min-w-0 max-w-full flex-col overflow-hidden border-r border-[#ffffff14] bg-[#0e0e10] lg:flex">
            <div class="w-full max-w-full shrink-0 overflow-visible border-b border-[#ffffff14] bg-[#0e0e10] px-4 py-5 sm:px-5">
                <div class="flex w-full min-w-0 items-center gap-2.5">
                    <button type="button" class="mobile-menu-button lg:hidden" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                        <span class="mobile-menu-mark" aria-hidden="true"></span>
                    </button>
                    <form method="GET" action="{{ route('dashboard.inbox') }}" class="min-w-0 flex-1">
                        <input type="hidden" name="state" value="{{ $activeState }}">
                        <input type="hidden" name="channel" value="{{ $activeChannel }}">
                        <input type="hidden" name="date" value="{{ $activeDate }}">
                        <input type="hidden" name="exact_date" value="{{ $activeExactDate }}">
                        <input type="hidden" name="time" value="{{ $activeTime }}">
                        <input type="hidden" name="sort" value="{{ $activeSort }}">
                        <label class="flex h-14 min-w-0 items-center gap-3 overflow-hidden rounded-[1.15rem] border border-[#ffffff14] bg-[#151517] px-4 text-[#8b8b91] shadow-sm transition">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5 shrink-0">
                                <path d="m21 21-4.35-4.35"></path>
                                <circle cx="11" cy="11" r="7"></circle>
                            </svg>
                            <span class="sr-only">Search conversations</span>
                            <input name="q" value="{{ $search }}" autocomplete="off" placeholder="Search conversations" class="inbox-search-input min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-medium text-white placeholder:text-[#8b8b91] focus:border-transparent focus:outline-none focus:ring-0 focus:shadow-none">
                            @if ($search !== '')
                                <a href="{{ route('dashboard.inbox', array_filter($filterQuery, fn ($value, $key) => $key !== 'q', ARRAY_FILTER_USE_BOTH)) }}" class="shrink-0 rounded-full px-1.5 text-xs font-bold text-[#8b8b91] hover:bg-[#0e0e10] hover:text-[#8b8b91]" aria-label="Clear search">x</a>
                            @endif
                        </label>
                    </form>
                </div>

                <div class="mt-5 flex items-center gap-3" x-data="{ channelOpen: false, stateOpen: false }">
                    <div class="relative">
                        <button type="button" x-on:click="channelOpen = ! channelOpen; stateOpen = false" class="flex h-11 items-center gap-3 rounded-[0.9rem] border border-[#ffffff14] bg-[#151517] px-4 text-sm font-semibold text-white transition hover:border-white/15" x-bind:aria-expanded="channelOpen.toString()">
                            <span>{{ $activeChannelLabel }}</span>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#8b8b91] transition" x-bind:class="channelOpen ? 'rotate-180' : ''">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>
                        <div x-cloak x-show="channelOpen" x-transition.origin.top.left x-on:click.outside="channelOpen = false" class="absolute left-0 top-14 z-40 w-72 overflow-hidden rounded-[1rem] border border-[#ffffff14] bg-[#151517] p-2 shadow-2xl shadow-black/40">
                            @foreach ($channelMeta as $channel => $meta)
                                @php
                                    $isChannelActive = $activeChannel === $channel;
                                    $channelUrl = route('dashboard.inbox', array_filter([
                                        'state' => $activeState === 'All' ? null : $activeState,
                                        'channel' => $channel === 'All' ? null : $channel,
                                        'q' => $search ?: null,
                                        'date' => $activeDate === 'all' ? null : $activeDate,
                                        'exact_date' => $activeExactDate ?: null,
                                        'time' => $activeTime === 'all' ? null : $activeTime,
                                        'sort' => $activeSort === 'newest' ? null : $activeSort,
                                    ]));
                                @endphp
                                <a href="{{ $channelUrl }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold transition {{ $isChannelActive ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91] hover:bg-[#0e0e10] hover:text-white' }}">
                                    <span class="flex items-center gap-3">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
                                            {!! $meta['icon'] !!}
                                        </svg>
                                        <span>{{ $channel === 'All' ? 'All channels' : $meta['label'] }}</span>
                                    </span>
                                    @if ($isChannelActive)
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 6 9 17l-5-5"></path></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" x-on:click="stateOpen = ! stateOpen; channelOpen = false" class="flex h-11 items-center gap-3 rounded-[0.9rem] border border-[#ffffff14] bg-[#151517] px-4 text-sm font-semibold text-white transition hover:border-white/15" x-bind:aria-expanded="stateOpen.toString()">
                            <span>{{ $activeStateLabel }}</span>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#8b8b91] transition" x-bind:class="stateOpen ? 'rotate-180' : ''">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>
                        <div x-cloak x-show="stateOpen" x-transition.origin.top.left x-on:click.outside="stateOpen = false" class="absolute left-0 top-14 z-40 w-56 overflow-hidden rounded-[1rem] border border-[#ffffff14] bg-[#151517] p-2 shadow-2xl shadow-black/40">
                            @foreach ($filterMeta as $state => $meta)
                                @php
                                    $isActive = $activeState === $state;
                                    $stateUrl = route('dashboard.inbox', array_filter([
                                        'state' => $state === 'All' ? null : $state,
                                        'channel' => $activeChannel === 'All' ? null : $activeChannel,
                                        'q' => $search ?: null,
                                        'date' => $activeDate === 'all' ? null : $activeDate,
                                        'exact_date' => $activeExactDate ?: null,
                                        'time' => $activeTime === 'all' ? null : $activeTime,
                                        'sort' => $activeSort === 'newest' ? null : $activeSort,
                                    ]));
                                @endphp
                                <a href="{{ $stateUrl }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold transition {{ $isActive ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91] hover:bg-[#0e0e10] hover:text-white' }}">
                                    <span>{{ $compactStateLabels[$state] ?? $meta['label'] }}</span>
                                    @if ($isActive)
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 6 9 17l-5-5"></path></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" x-on:click="filtersOpen = true" class="relative ml-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-[0.9rem] border border-[#ffffff14] bg-[#151517] text-white shadow-sm transition hover:border-white/15" aria-label="Open filters">
                        @if ($advancedFiltersActive)
                            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[#0e0e10]"></span>
                        @endif
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M4 7h16"></path><path d="M4 17h16"></path><circle cx="9" cy="7" r="2"></circle><circle cx="15" cy="17" r="2"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div x-cloak x-show="filtersOpen" class="fixed inset-0 z-50 bg-black/35" x-on:click.self="filtersOpen = false" x-on:close-filters="filtersOpen = false">
                <div class="absolute inset-x-3 bottom-3 sm:inset-x-auto sm:bottom-auto sm:left-[31rem] sm:top-40 sm:w-[22rem]">
                <div x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-2 opacity-0" class="rounded-[1.25rem] border border-[#ffffff14] bg-[#151517] px-5 pb-5 pt-5 shadow-2xl shadow-black/50 will-change-transform">
                    <div class="hidden"></div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">Filter</h3>
                        <button type="button" x-on:click="filtersOpen = false" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#0e0e10] text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-[#8b8b91]" aria-label="Close filters">
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
                            <div x-data="window.inboxFilterMenu(@js($activeDate), @js($dateOptions))" x-on:click.outside="open = false" data-filter-kind="date" class="relative min-w-0">
                                <input type="hidden" name="date" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#8b8b91]">Date</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-3 text-left text-sm font-semibold text-[#8b8b91] shadow-sm transition hover:bg-[#0e0e10] focus:outline-none focus:ring-2 focus:ring-[#8b8b91]/30">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#8b8b91]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#8b8b91] bg-[#151517] p-1 shadow-xl shadow-black/30">
                                    @foreach ($dateOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#0e0e10]" x-bind:class="value === @js($value) ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div x-data="window.inboxDatePicker()" class="inbox-date-picker min-w-0">
                                <label for="inbox-exact-date" class="mb-2 block text-xs font-semibold text-[#8b8b91]">Specific date</label>
                                <div class="relative">
                                    <input
                                        x-ref="input"
                                        id="inbox-exact-date"
                                        type="text"
                                        name="exact_date"
                                        value="{{ $activeExactDate }}"
                                        placeholder="Choose a date"
                                        autocomplete="off"
                                        class="h-12 w-full cursor-pointer rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-3 pr-11 text-sm font-semibold text-[#8b8b91] shadow-sm transition hover:bg-[#0e0e10]"
                                    >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute right-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-[#8b8b91]">
                                        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                                        <path d="M16 3v4M8 3v4M3 10h18"></path>
                                    </svg>
                                </div>
                            </div>
                            <div x-data="window.inboxFilterMenu(@js($activeTime), @js($timeOptions))" x-on:click.outside="open = false" class="relative min-w-0">
                                <input type="hidden" name="time" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#8b8b91]">Time</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-3 text-left text-sm font-semibold text-[#8b8b91] shadow-sm transition hover:bg-[#0e0e10] focus:outline-none focus:ring-2 focus:ring-[#8b8b91]/30">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#8b8b91]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#8b8b91] bg-[#151517] p-1 shadow-xl shadow-black/30">
                                    @foreach ($timeOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#0e0e10]" x-bind:class="value === @js($value) ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div x-data="window.inboxFilterMenu(@js($activeSort), @js($sortOptions))" x-on:click.outside="open = false" class="relative min-w-0">
                                <input type="hidden" name="sort" x-bind:value="value">
                                <p class="mb-2 text-xs font-semibold text-[#8b8b91]">Sort by</p>
                                <button type="button" x-on:click="open = ! open" class="flex h-12 w-full min-w-0 items-center justify-between gap-2 rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-3 text-left text-sm font-semibold text-[#8b8b91] shadow-sm transition hover:bg-[#0e0e10] focus:outline-none focus:ring-2 focus:ring-[#8b8b91]/30">
                                    <span class="truncate" x-text="label"></span>
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-[#8b8b91]" x-bind:class="open ? 'rotate-180' : ''">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute bottom-14 left-0 z-50 w-full overflow-hidden rounded-xl border border-[#8b8b91] bg-[#151517] p-1 shadow-xl shadow-black/30">
                                    @foreach ($sortOptions as $value => $label)
                                        <button type="button" x-on:click="choose(@js($value))" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition hover:bg-[#0e0e10]" x-bind:class="value === @js($value) ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91]'">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end">
                            <a href="{{ route('dashboard.inbox', array_filter(['state' => $activeState === 'All' ? null : $activeState, 'channel' => $activeChannel === 'All' ? null : $activeChannel, 'q' => $search ?: null])) }}" class="rounded-xl bg-[#0e0e10] px-7 py-3 text-sm font-bold text-[#8b8b91] shadow-sm transition hover:bg-[#0e0e10] hover:text-[#8b8b91]">Reset</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div x-ref="conversationScroller" data-conversation-scroller class="min-h-0 w-full max-w-full flex-1 overflow-y-auto overflow-x-hidden bg-[#0e0e10]">
                <div x-ref="conversationList" data-conversation-list data-next-cursor="{{ $nextConversationCursor }}">
                @forelse ($conversations as $conversation)
                    @php
                        $intent = $conversation->getAttribute('intent');
                        $unreadCount = $conversation->getAttribute('unread_count');
                        $statusMeta = $conversation->getAttribute('status_meta');
                        $channel = $conversation->getAttribute('channel_meta');
                        $latestReplyDisabled = (bool) $conversation->getAttribute('reply_disabled');
                        $avatarUrl = $conversation->customer?->avatarUrl();
                        $lastMessageAt = $conversation->last_message_at;
                        $lastMessageLabel = $lastMessageAt
                            ? ($lastMessageAt->lt(now()->subHours(24))
                                ? $lastMessageAt->format($lastMessageAt->year === now()->year ? 'd M' : 'd M y')
                                : $lastMessageAt->format('H:i'))
                            : '';
                    @endphp
                    <a href="{{ route('dashboard.inbox', $filterQuery + ['conversation' => $conversation->id]) }}" class="group block w-full min-w-0 max-w-full overflow-hidden px-4 transition hover:bg-[#151517] sm:px-5 {{ $selectedConversation?->id === $conversation->id ? 'bg-[#0e0e10]' : '' }}">
                        <div class="flex w-full min-w-0 max-w-full gap-3 overflow-hidden border-b border-[#8b8b91] py-3.5">
                            <span class="relative mt-1 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm sm:h-12 sm:w-12 {{ $avatarUrl ? 'bg-[#0e0e10]' : $channel['class'] }}" title="{{ $conversation->channel }}" aria-label="{{ $conversation->channel }}">
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
                                    <p class="min-w-0 flex-1 truncate text-[15px] font-semibold text-[#8b8b91]">{{ $conversation->customer_name }}</p>
                                    <time datetime="{{ $lastMessageAt?->toIso8601String() }}" title="{{ $lastMessageAt?->format('d M Y, H:i') }}" class="shrink-0 text-xs font-semibold {{ $unreadCount > 0 ? 'text-[#8b8b91]' : 'text-[#8b8b91]' }}">{{ $lastMessageLabel }}</time>
                                </div>
                                <div class="mt-1 flex min-w-0 items-center justify-between gap-2">
                                    <p class="min-w-0 flex-1 truncate text-sm text-[#8b8b91]">
                                        @if ($conversation->status === 'Waiting')
                                            <span class="mr-1 inline-flex text-[#8b8b91]">
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
                                        <span class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#8b8b91] px-1.5 text-xs font-bold text-white" aria-label="{{ $unreadCount }} unread message">{{ $unreadCount }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $statusMeta['class'] }}" title="{{ $statusMeta['label'] }}" aria-label="{{ $statusMeta['label'] }}">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                            {!! $statusMeta['icon'] !!}
                                        </svg>
                                    </span>
                                    <span class="hidden items-center gap-1 rounded-full bg-[#0e0e10] px-2 py-0.5 text-xs font-semibold text-[#8b8b91] sm:inline-flex" title="{{ $intent }}">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                            <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"></path>
                                            <path d="M7.5 7.5h.01"></path>
                                        </svg>
                                        {{ $intent }}
                                    </span>
                                    @if ($latestReplyDisabled)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#0e0e10] px-2 py-0.5 text-xs font-semibold text-[#8b8b91]" title="Replies disabled">
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
                    <div class="p-4">
                        <div class="flex min-h-[34rem] flex-col items-center justify-center rounded-[1.4rem] border border-[#ffffff14] bg-[#151517] px-6 py-12 text-center">
                            <div class="relative mb-8 flex h-32 w-40 items-center justify-center">
                                <span class="absolute bottom-2 h-6 w-28 rounded-full bg-white/5 blur-xl"></span>
                                <span class="absolute left-3 top-8 text-white/30">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-5 w-5"><path d="M12 2v20M2 12h20"></path></svg>
                                </span>
                                <span class="absolute right-4 top-10 h-2 w-2 rounded-full border border-white/25"></span>
                                <div class="absolute right-5 top-12 h-16 w-20 rounded-[1.35rem] border border-[#ffffff14] bg-[#0e0e10]"></div>
                                <div class="relative h-20 w-28 rounded-[1.45rem] border border-[#ffffff14] bg-[#151517] shadow-[0_0_32px_rgba(255,255,255,0.04)]">
                                    <div class="absolute left-8 top-9 flex gap-2">
                                        <span class="h-2 w-2 rounded-full bg-white/70"></span>
                                        <span class="h-2 w-2 rounded-full bg-white/55"></span>
                                        <span class="h-2 w-2 rounded-full bg-white/40"></span>
                                    </div>
                                    <span class="absolute -bottom-3 left-8 h-6 w-6 rotate-45 rounded-br-md border-b border-r border-[#ffffff14] bg-[#151517]"></span>
                                </div>
                            </div>
                            <h3 class="text-2xl font-semibold tracking-[-0.03em] text-white">No conversations yet</h3>
                            <p class="mt-4 max-w-[18rem] text-sm leading-6 text-[#8b8b91]">Once conversations come in, you’ll see them here.</p>
                        </div>
                    </div>
                @endforelse
                </div>
                <div x-ref="conversationSentinel" class="px-5 py-4 text-center text-xs font-semibold text-[#8b8b91]" x-show="nextConversationCursor || loadingConversations">
                    <span x-show="loadingConversations">Loading older conversations…</span>
                    <span x-show="! loadingConversations && nextConversationCursor">Scroll for more</span>
                </div>
            </div>
        </aside>

        <section class="{{ $conversationIsOpen ? 'flex' : 'hidden' }} h-full min-h-0 flex-col bg-[#0e0e10] lg:flex">
            @if ($selectedConversation)
                @php
                    $statusClass = match ($selectedConversation->status) {
                        'AI Handling' => 'text-[#8b8b91]',
                        'Waiting' => 'text-[#8b8b91]',
                        'Needs Human' => 'text-[#8b8b91]',
                        default => 'text-[#8b8b91]',
                    };
                    $replyDisabled = $selectedConversation->replyDisabled();
                    $replyDisabledReason = $selectedConversation->replyDisabledReason() ?? 'Automated or not replyable email';
                    $isGmailThread = $selectedConversation->channel === 'Gmail';
                    $gmailThreadMessageCount = $isGmailThread ? $selectedConversation->messages->where('metadata.source', 'gmail')->count() : 0;
                    $selectedAvatarUrl = $selectedConversation->customer?->avatarUrl();
                    $selectedChannel = \App\Support\InboxUi::channelMeta($selectedConversation->channel);
                @endphp
                <div class="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-[#8b8b91] bg-[#0e0e10] px-3 py-3 sm:px-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <a href="{{ route('dashboard.inbox', $filterQuery) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#8b8b91] hover:bg-[#0e0e10] lg:hidden" aria-label="Back to messages">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M15 18 9 12l6-6"></path>
                            </svg>
                        </a>
                        <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#0e0e10] text-sm font-bold text-[#8b8b91]">
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
                            <h3 class="truncate text-base font-semibold text-[#8b8b91]">{{ $selectedConversation->customer_name }}</h3>
                            <p class="truncate text-xs font-semibold {{ $statusClass }}">
                                {{ $selectedConversation->status }} / {{ $selectedConversation->channel }}
                                @if ($isGmailThread && $gmailThreadMessageCount > 1)
                                    <span class="text-[#8b8b91]">/ {{ $gmailThreadMessageCount }} emails</span>
                                @endif
                            </p>
                        </button>
                    </div>
                </div>

                <div data-chat-scroll class="min-h-0 flex-1 space-y-2.5 overflow-x-hidden overflow-y-auto bg-[#0e0e10] p-3 sm:p-5">
                    @foreach ($selectedConversation->messages as $message)
                        @php
                            $isGmailMessage = $selectedConversation->channel === 'Gmail' || ($message->metadata['source'] ?? null) === 'gmail';
                            $gmailSubject = $message->metadata['subject'] ?? null;
                            $gmailFrom = $message->metadata['from_email'] ?? $selectedConversation->customer_external_id;
                            $gmailTo = $message->metadata['to_email'] ?? null;
                            $gmailHtmlBody = $message->metadata['gmail_html_body'] ?? null;
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
                            $gmailFileLinks = $isGmailMessage
                                ? \App\Support\MessageText::fileLinks(trim($messageBody."\n".(string) $gmailHtmlBody))
                                : [];
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
                            <div class="pointer-events-none absolute top-1/2 z-0 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-[#0e0e10] text-white opacity-0 shadow-sm transition {{ $message->direction === 'outgoing' ? 'right-2' : 'left-2' }}" x-bind:class="Math.abs(offsetX) > 18 ? 'opacity-100' : 'opacity-0'">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="m9 14-4-4 4-4"></path>
                                    <path d="M5 10h10a4 4 0 0 1 4 4v1"></path>
                                </svg>
                            </div>
                            <div class="relative z-10 min-w-0 {{ $isGmailMessage ? 'max-w-[94%] sm:max-w-[82%]' : ($mediaOnlyVisual ? 'max-w-[76vw] sm:max-w-[22rem]' : ($mediaOnlyAudio ? 'w-[16.5rem] max-w-[86%]' : 'max-w-[84%] sm:max-w-[68%]')) }} transition-transform duration-150 ease-out" x-bind:style="`transform: translateX(${offsetX}px)`">
                                <div class="rounded-2xl border text-sm {{ $mediaOnlyVisual ? 'overflow-hidden px-1 pb-1 pt-1' : ($mediaOnlyAudio ? 'px-2.5 py-2' : 'px-3 py-2') }} {{ $mediaOnlyAttachment ? 'shadow-none' : 'shadow-sm' }} {{ $message->direction === 'outgoing' ? 'border-[#ffffff14] bg-[#0e0e10] text-white' : 'border-[#8b8b91] bg-white text-[#8b8b91]' }}">
                                    @if ($replyContext)
                                        <div class="mb-2 border-l-2 border-[#8b8b91] bg-white/60 px-2 py-1.5">
                                            <p class="truncate text-xs font-bold text-[#8b8b91]">{{ $replyContext['sender'] ?? 'Message' }}</p>
                                            <p class="truncate text-xs text-[#8b8b91]">{{ $replyContext['body'] ?? 'Attachment' }}</p>
                                        </div>
                                    @endif
                                    @if ($isGmailMessage)
                                        <div class="mb-3 border-b border-[#8b8b91] pb-3">
                                        <div class="flex items-center gap-2 text-xs font-bold text-[#8b8b91]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#ea4335]">
                                                <path d="M4 6h16v12H4z"></path>
                                                <path d="m4 7 8 6 8-6"></path>
                                            </svg>
                                            {{ $gmailSenderLabel }}
                                            @if ($gmailThreadMessageCount > 1)
                                                <span class="rounded-full bg-[#0e0e10] px-2 py-0.5 text-[10px] text-[#8b8b91]">Thread {{ $loop->iteration }} of {{ $gmailThreadMessageCount }}</span>
                                            @endif
                                            @if ($gmailReplyDisabled)
                                                <span class="rounded-full bg-[#0e0e10] px-2 py-0.5 text-[10px] text-[#8b8b91]">{{ $gmailReplyDisabledReason }}</span>
                                            @endif
                                        </div>
                                        @if ($gmailSubject)
                                            <h4 class="mt-2 break-words text-base font-bold leading-6 text-[#8b8b91]">{{ $gmailSubject }}</h4>
                                        @endif
                                        <div class="mt-2 space-y-1 text-xs font-semibold text-[#8b8b91]">
                                            <p class="break-all"><span class="text-[#8b8b91]">From</span> {{ $gmailFrom }}</p>
                                            @if ($gmailTo)
                                                <p class="break-all"><span class="text-[#8b8b91]">To</span> {{ $gmailTo }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($gmailHtmlBody)
                                        <div class="overflow-hidden rounded-2xl border border-[#8b8b91] bg-[#0e0e10]">
                                            {!! \App\Support\MessageText::gmailFrame($gmailHtmlBody, $message->attachments) !!}
                                        </div>
                                    @else
                                        <div class="whitespace-pre-line break-words leading-6 text-[#8b8b91]">{!! \App\Support\MessageText::linkify($messageBody !== '' ? $messageBody : '(empty email)') !!}</div>
                                    @endif
                                    @if ($gmailFileLinks !== [])
                                        <div class="mt-3 space-y-2">
                                            @foreach ($gmailFileLinks as $fileLink)
                                                <a href="{{ $fileLink['url'] }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl border border-[#8b8b91] bg-[#0e0e10] p-3 transition hover:border-[#8b8b91] hover:bg-[#0e0e10]">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#0e0e10] text-white">
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                            <path d="M14 2v6h6"></path>
                                                            <path d="M8 13h8"></path>
                                                            <path d="M8 17h5"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-sm font-bold text-[#8b8b91]">{{ $fileLink['label'] }}</span>
                                                        <span class="mt-0.5 block truncate text-xs font-semibold text-[#8b8b91]">{{ $fileLink['type'] }} / Opens from Google</span>
                                                    </span>
                                                    <span class="shrink-0 text-[#8b8b91]">
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                            <path d="M7 17 17 7"></path>
                                                            <path d="M7 7h10v10"></path>
                                                        </svg>
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    @unless ($mediaOnlyPlaceholder)
                                        <p class="mb-1 text-xs font-bold {{ $message->sender_type === 'ai' ? 'text-[#8b8b91]' : 'text-[#8b8b91]' }}">{{ $senderLabel }}</p>
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
                                                $hasInlinePreview = $isImage || $isAudio || $isVideo || $isPdf;
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
                                                    <button type="button" x-on:click.stop="openMedia({ type: 'image', src: @js($inlineUrl), alt: @js($attachment->filename) })" data-media-frame class="media-preview-frame media-preview-frame--image block overflow-hidden rounded-xl bg-[#0e0e10] text-left transition hover:opacity-95" aria-label="Open image preview">
                                                        <img src="{{ $inlineUrl }}" alt="{{ $attachment->filename }}" loading="lazy" decoding="async" x-on:load="$event.target.closest('[data-media-frame]')?.classList.add('media-loaded')" x-on:error="markMediaFailed($event, 'Image unavailable')" class="block max-h-[18rem] max-w-full rounded-xl object-contain">
                                                    </button>
                                                </div>
                                            @elseif ($isVideo)
                                                <div data-media-frame class="media-preview-frame media-preview-frame--video overflow-hidden rounded-xl bg-[#8b8b91]">
                                                    <button type="button" x-on:click.stop="openMedia({ type: 'video', src: @js($inlineUrl), alt: @js($attachment->filename) })" class="group relative block aspect-[4/5] max-h-[20rem] w-auto max-w-full overflow-hidden rounded-xl bg-[#8b8b91] text-white" aria-label="Open video">
                                                        <video playsinline preload="metadata" src="{{ $inlineUrl }}" x-on:loadedmetadata="$event.target.closest('[data-media-frame]')?.classList.add('media-loaded')" x-on:error="markMediaFailed($event, 'Video unavailable')" class="h-full max-h-[20rem] w-auto max-w-full object-contain"></video>
                                                        <span class="absolute inset-0 flex items-center justify-center bg-black/10 transition group-hover:bg-black/20">
                                                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/90 text-[#8b8b91] shadow-lg">
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
                                                        <button type="button" x-on:click="toggle" x-bind:class="ready ? 'bg-[#0e0e10] text-white hover:bg-[#151517]' : 'bg-[#0e0e10] text-[#8b8b91]'" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full shadow-sm transition" aria-label="Play voice note">
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
                                                        <span class="text-[11px] font-semibold tabular-nums text-[#8b8b91]" x-text="displayTime"></span>
                                                    </div>
                                                </div>
                                            @elseif ($isPdf)
                                                <div data-media-frame class="media-preview-frame media-preview-frame--pdf overflow-hidden rounded-xl border border-[#8b8b91] bg-[#0e0e10]">
                                                    <button type="button" x-on:click.stop="openMedia({ type: 'pdf', src: @js($inlineUrl), alt: @js($attachment->filename) })" class="block w-full text-left" aria-label="Open PDF preview">
                                                        <div class="flex items-center gap-3 border-b border-[#8b8b91] bg-[#0e0e10] px-3 py-2.5">
                                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-pink-50 text-[#8b8b91]">
                                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                                    <path d="M14 2v6h6"></path>
                                                                    <path d="M8 16h8"></path>
                                                                    <path d="M8 12h4"></path>
                                                                </svg>
                                                            </span>
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate text-sm font-bold text-[#8b8b91]">{{ $attachment->filename }}</span>
                                                                <span class="block truncate text-xs font-semibold text-[#8b8b91]">PDF / {{ $sizeLabel }}</span>
                                                            </span>
                                                            <span class="text-[#8b8b91]">
                                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                                    <path d="m9 18 6-6-6-6"></path>
                                                                </svg>
                                                            </span>
                                                        </div>
                                                    </button>
                                                    <iframe src="{{ $inlineUrl }}" title="{{ $attachment->filename }}" class="block h-[18rem] w-full bg-[#0e0e10]" loading="lazy"></iframe>
                                                </div>
                                            @endif
                                            @unless ($hasInlinePreview)
                                            <a href="{{ route('dashboard.attachments.download', $attachment) }}" class="flex items-center gap-3 rounded-xl border border-[#8b8b91] bg-[#0e0e10] p-3 transition hover:border-[#8b8b91] hover:bg-[#0e0e10]">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $isPdf ? 'bg-[#0e0e10] text-white' : ($isImage ? 'bg-[#0e0e10] text-white' : ($isVideo ? 'bg-[#0e0e10] text-white' : ($isAudio ? 'bg-[#0e0e10] text-white' : 'bg-[#0e0e10] text-[#8b8b91]'))) }}">
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
                                                    <span class="block truncate text-sm font-bold text-[#8b8b91]">{{ $attachment->filename }}</span>
                                                    <span class="mt-0.5 block truncate text-xs font-semibold text-[#8b8b91]">{{ $attachment->mime_type ?: 'File' }} / {{ $sizeLabel }}</span>
                                                </span>
                                                <span class="shrink-0 text-[#8b8b91]">
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
                            <div class="mt-1 flex justify-end gap-1 pr-1 text-[11px] text-[#8b8b91]">
                                <span>{{ $message->created_at?->format('H:i') }}</span>
                                @if ($message->direction === 'outgoing')
                                    <span class="inline-flex text-[#8b8b91]">
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

                <div class="chat-composer shrink-0 border-t border-[#8b8b91] bg-[#0e0e10]">
                    @if ($replyDisabled)
                        <div class="flex items-center gap-2 rounded-xl border border-[#8b8b91] bg-[#0e0e10] p-2 sm:justify-between">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0e0e10] text-[#8b8b91]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M22 2 11 13"></path>
                                        <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                        <path d="m2 2 20 20"></path>
                                    </svg>
                                </span>
                                <div class="min-w-0 leading-tight">
                                    <p class="text-xs font-bold text-[#8b8b91]">Replies disabled</p>
                                    <p class="truncate text-[0.68rem] font-medium text-[#8b8b91]">Looks like {{ strtolower($replyDisabledReason) }}.</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('dashboard.inbox.close', $selectedConversation) }}" class="shrink-0">
                                @csrf
                                <button class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#0e0e10] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#151517]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Mark reviewed</span>
                                    <span class="sm:hidden">Done</span>
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
                            <div x-show="replyTo" x-cloak x-transition class="rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-3 py-2 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0e0e10] text-white">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="m9 14-4-4 4-4"></path>
                                            <path d="M5 10h10a4 4 0 0 1 4 4v1"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 border-l-2 border-[#8b8b91] pl-3">
                                        <p class="truncate text-xs font-bold text-[#8b8b91]" x-text="replyTo?.sender || 'Message'"></p>
                                        <p class="truncate text-xs text-[#8b8b91]" x-text="replyTo?.body || 'Attachment'"></p>
                                    </div>
                                    <button type="button" x-on:click="clearReply" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-[#8b8b91]" aria-label="Cancel reply">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" class="h-4 w-4">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div x-show="recording || voiceNoteReady || recordError" x-cloak x-transition class="px-1">
                                <div x-show="recording" class="flex items-center gap-3 rounded-xl border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 shadow-sm">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#ffffff] shadow-sm">
                                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-[#ffffff]"></span>
                                    </span>
                                    <div x-ref="recordWaveform" class="h-8 min-w-0 flex-1"></div>
                                    <span class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-[#ffffff]" x-text="recordElapsed"></span>
                                    <button type="button" x-on:click="stopRecorder" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#ffffff] shadow-sm transition hover:bg-[#0e0e10]" aria-label="Stop voice note" title="Stop voice note">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5">
                                            <rect x="7" y="7" width="10" height="10" rx="2"></rect>
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="! recording && voiceNoteReady" class="flex items-center gap-3 rounded-xl border border-[#ffffff14] bg-[#0e0e10] px-3 py-2 shadow-sm">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0e0e10] text-white shadow-sm">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                            <path d="M19 11a7 7 0 0 1-14 0"></path>
                                            <path d="M12 18v3"></path>
                                        </svg>
                                    </span>
                                    <div class="flex min-w-0 flex-1 items-center gap-1.5 text-white" aria-hidden="true">
                                        @for ($i = 0; $i < 24; $i++)
                                            <span class="w-0.5 rounded-full bg-current opacity-70" style="height: {{ [8, 12, 16, 10, 20, 14, 24, 12][$i % 8] }}px"></span>
                                        @endfor
                                    </div>
                                    <span class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-white" x-text="recordedVoiceElapsed"></span>
                                    <button type="button" x-on:click="clearVoiceNote" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#8b8b91] shadow-sm transition hover:text-[#ffffff]" aria-label="Discard voice note" title="Discard voice note">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="recordError" class="rounded-xl border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 text-xs font-semibold text-[#ffffff]" x-text="recordError"></div>
                            </div>
                            <div x-show="mediaFileCount > 0" x-cloak x-transition class="rounded-xl border border-[#8b8b91] bg-white px-2 py-2 shadow-sm">
                                <input x-ref="imageInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-images-{{ $selectedConversation->id }}" multiple accept="image/*,video/*">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex min-h-12 min-w-0 flex-1 items-center gap-1 rounded-xl border border-[#8b8b91] bg-[#0e0e10] px-2 sm:gap-2 sm:px-3">
                                    <div class="relative shrink-0" x-on:click.outside="emojiOpen = false">
                                        <button type="button" x-on:click="emojiOpen = ! emojiOpen" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-white sm:h-9 sm:w-9" aria-label="Add emoji" title="Add emoji">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M8.5 10h.01"></path>
                                                <path d="M15.5 10h.01"></path>
                                                <path d="M8 14.5c1.1 1 2.4 1.5 4 1.5s2.9-.5 4-1.5"></path>
                                            </svg>
                                        </button>
                                        <div x-cloak x-show="emojiOpen" x-transition class="absolute bottom-11 left-0 z-[80] max-h-72 w-72 overflow-y-auto rounded-xl border border-[#8b8b91] bg-white p-3 shadow-xl shadow-slate-900/10">
                                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Emoji</p>
                                            <div class="grid grid-cols-8 gap-1">
                                                @foreach (['&#128512;', '&#128513;', '&#128514;', '&#128522;', '&#128525;', '&#128526;', '&#129392;', '&#128578;', '&#128077;', '&#128079;', '&#128591;', '&#128170;', '&#128076;', '&#9996;', '&#128075;', '&#129309;', '&#9989;', '&#128204;', '&#128293;', '&#127881;', '&#128153;', '&#128154;', '&#128155;', '&#10084;&#65039;', '&#128172;', '&#128197;', '&#128276;', '&#128269;', '&#128640;', '&#128161;', '&#9200;', '&#128176;', '&#128221;', '&#128206;', '&#128247;', '&#127908;', '&#128226;', '&#9888;', '&#128721;', '&#11088;'] as $emoji)
                                                    <button type="button" x-on:click="insertEmoji($event.currentTarget.textContent.trim())" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg transition hover:bg-[#0e0e10]" aria-label="Insert emoji">{!! $emoji !!}</button>
                                                @endforeach
                                            </div>
                                            <button type="button" x-on:click="emojiOpen = false" class="mt-3 w-full rounded-lg border border-[#8b8b91] bg-[#0e0e10] px-3 py-2 text-xs font-bold text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-white">Close</button>
                                        </div>
                                    </div>
                                    <textarea x-ref="messageInput" x-on:input="updateTyping" name="body" rows="1" class="min-w-[5rem] max-h-28 flex-1 resize-none border-0 bg-transparent py-3 text-sm text-[#8b8b91] placeholder:text-[#8b8b91] focus:ring-0" placeholder="Message"></textarea>
                                    <input x-ref="fileInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-attachments-{{ $selectedConversation->id }}" multiple class="hidden">
                                    @unless ($voiceNotesDisabled)
                                        <input x-ref="audioInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-audio-{{ $selectedConversation->id }}" accept="audio/*" class="hidden">
                                    @endunless
                                    <div x-bind:class="composerHasText ? 'w-0 max-w-0 scale-95 opacity-0 pointer-events-none overflow-hidden' : 'w-auto max-w-[15rem] scale-100 opacity-100'" class="flex shrink-0 items-center gap-1 transition-all duration-200 ease-out sm:gap-2">
                                        <label for="message-attachments-{{ $selectedConversation->id }}" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-white sm:h-9 sm:w-9" aria-label="Attach file" title="Attach file">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 1 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                            </svg>
                                        </label>
                                        <button type="button" x-on:click="browseMedia" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-white sm:h-9 sm:w-9" aria-label="Upload media" title="Upload image or video">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <rect x="3" y="5" width="18" height="14" rx="2.5"></rect>
                                                <circle cx="8.5" cy="10" r="1.5"></circle>
                                                <path d="m21 15-4.5-4.5L8 19"></path>
                                            </svg>
                                        </button>
                                        @if ($voiceNotesDisabled)
                                            <span class="relative inline-flex h-8 w-8 shrink-0 cursor-not-allowed items-center justify-center rounded-md text-[#8b8b91] sm:h-9 sm:w-9" aria-label="Voice notes are disabled for email" title="Voice notes are disabled for email">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                    <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                                    <path d="M19 11a7 7 0 0 1-14 0"></path>
                                                    <path d="M12 18v3"></path>
                                                </svg>
                                                <span class="absolute h-px w-6 rotate-45 bg-[#8b8b91]"></span>
                                            </span>
                                        @else
                                            <button type="button" x-on:click="toggleRecorder" x-bind:class="recording ? 'bg-[#0e0e10] text-white' : 'text-[#8b8b91] hover:bg-[#0e0e10] hover:text-white'" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition sm:h-9 sm:w-9" aria-label="Record voice note" title="Record voice note">
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
                                        <button x-show="! automationPaused" x-on:click="automationPaused = true" data-instant-action="true" formaction="{{ route('dashboard.inbox.take-over', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#0e0e10] text-white transition hover:bg-[#151517] sm:h-9 sm:w-9" aria-label="Automation active. Pause automation." title="Automation active">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                    <path d="M10 5v14"></path>
                                                    <path d="M14 5v14"></path>
                                                </svg>
                                        </button>
                                        <button x-cloak x-show="automationPaused" x-on:click="automationPaused = false" data-instant-action="true" formaction="{{ route('dashboard.inbox.resume-ai', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[#8b8b91] transition hover:bg-[#0e0e10] hover:text-white sm:h-9 sm:w-9" aria-label="Automation paused. Resume automation." title="Automation paused">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M7.5 5.7c0-.9 1-1.4 1.8-.9l8.6 5.3c.7.4.7 1.4 0 1.8l-8.6 5.3c-.8.5-1.8-.1-1.8-.9V5.7Z"></path>
                                                </svg>
                                        </button>
                                    </div>
                                </div>
                                <button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#0e0e10] text-white shadow-sm transition hover:bg-[#151517]" aria-label="Send message">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                        <path d="M22 2 11 13"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 px-1">
                                <template x-for="file in genericFiles" :key="file.name">
                                    <span x-show="genericFiles.length > 0" x-cloak class="inline-flex max-w-full items-center gap-2 rounded-lg bg-[#0e0e10] px-3 py-2 text-xs font-semibold text-[#8b8b91]">
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
                <div class="flex h-full items-center justify-center bg-[#0e0e10] p-8 text-sm text-[#8b8b91]">Select a conversation.</div>
            @endif
        </section>

        <aside class="hidden h-full min-h-0 overflow-y-auto border-l border-[#8b8b91] bg-white p-5 xl:block">
            @if ($selectedConversation)
                <h3 class="font-bold text-[#8b8b91]">Customer profile</h3>
                <div class="mt-5 flex items-center gap-3 rounded-xl bg-[#0e0e10] p-4">
                    <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-sm font-bold text-[#8b8b91]">
                        @if ($selectedAvatarUrl)
                            <img src="{{ $selectedAvatarUrl }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-[#8b8b91]">{{ $selectedConversation->customer_name }}</p>
                        <p class="truncate text-xs font-semibold text-[#8b8b91]">{{ $selectedConversation->channel }}</p>
                    </div>
                    @if ($canDeleteConversations)
                        <form method="POST" action="{{ route('dashboard.inbox.destroy', $selectedConversation) }}" onsubmit="return confirm('Delete this entire conversation? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#8b8b91] transition hover:bg-red-50 hover:text-red-600" title="Delete conversation" aria-label="Delete conversation">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M3 6h18"></path>
                                    <path d="M8 6V4h8v2"></path>
                                    <path d="m19 6-1 14H6L5 6"></path>
                                    <path d="M10 11v5M14 11v5"></path>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Name</dt>
                        <dd class="mt-1 font-bold text-[#8b8b91]">{{ $selectedConversation->customer_name }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Channel</dt>
                        <dd class="mt-1 font-bold text-[#8b8b91]">{{ $selectedConversation->channel }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">{{ $customerIdentityLabel }}</dt>
                        <dd class="mt-1 font-mono text-xs font-semibold text-[#8b8b91]">{{ $selectedConversation->customer_external_id }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Mode</dt>
                        <dd class="mt-1 font-bold text-[#8b8b91]">{{ ucfirst($selectedConversation->ai_mode) }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Detected intent</dt>
                        <dd class="mt-2 inline-flex rounded-full bg-[#0e0e10] px-3 py-1 text-xs font-bold text-[#8b8b91]">{{ $selectedConversation->getAttribute('detected_intent') }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Notes</dt>
                        <dd class="mt-1 leading-6 text-[#8b8b91]">{{ $selectedConversation->customer?->notes ?? 'No notes yet.' }}</dd>
                    </div>
                    <div class="rounded-xl bg-[#0e0e10] p-4">
                        <dt class="font-semibold text-[#8b8b91]">Tags</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#8b8b91]">{{ $selectedConversation->status }}</span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#8b8b91]">{{ $selectedConversation->channel }}</span>
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
            <button type="button" x-on:click="closeMedia" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-[#10131a]/95 text-white shadow-lg transition hover:bg-[#0e0e10]" aria-label="Close media preview">
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
                    <video x-ref="mediaVideo" x-bind:src="mediaViewer.src" controls playsinline preload="metadata" class="h-[82dvh] max-h-[82dvh] w-auto max-w-[96vw] rounded-2xl bg-[#8b8b91] object-contain shadow-2xl sm:h-[84dvh]"></video>
                </template>
                <template x-if="mediaViewer.type === 'pdf'">
                    <iframe x-bind:src="mediaViewer.src" class="h-[88dvh] w-[96vw] max-w-4xl rounded-2xl bg-white shadow-2xl" title="PDF preview"></iframe>
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
                    class="absolute inset-x-3 bottom-3 max-h-[82vh] overflow-y-auto rounded-2xl border border-[#8b8b91] bg-white shadow-2xl shadow-slate-900/20"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Customer profile"
                    x-on:click.stop
                >
                    <div class="flex items-center justify-between border-b border-[#8b8b91] px-4 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#0e0e10] text-sm font-bold text-[#8b8b91]">
                                @if ($selectedAvatarUrl)
                                    <img src="{{ $selectedAvatarUrl }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-[#8b8b91]">{{ $selectedConversation->customer_name }}</h3>
                                <p class="mt-0.5 truncate text-xs font-bold {{ $statusClass }}">{{ $selectedConversation->status }} / {{ $selectedConversation->channel }}</p>
                            </div>
                        </div>
                        <button type="button" x-on:click="profileOpen = false" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0e0e10] text-[#8b8b91]" aria-label="Close customer profile">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <dl class="grid gap-3 p-4 text-sm">
                        <div class="rounded-xl bg-[#0e0e10] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Channel</dt>
                            <dd class="mt-1 font-bold text-[#8b8b91]">{{ $selectedConversation->channel }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#0e0e10] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Mode</dt>
                            <dd class="mt-1 font-bold text-[#8b8b91]">{{ ucfirst($selectedConversation->ai_mode) }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#0e0e10] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Detected intent</dt>
                            <dd class="mt-2 inline-flex rounded-full bg-[#0e0e10] px-3 py-1 text-xs font-bold text-[#8b8b91]">{{ $selectedConversation->getAttribute('detected_intent') }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#0e0e10] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">{{ $customerIdentityLabel }}</dt>
                            <dd class="mt-1 break-all font-mono text-xs font-semibold text-[#8b8b91]">{{ $selectedConversation->customer_external_id }}</dd>
                        </div>
                        <div class="rounded-xl bg-[#0e0e10] p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Notes</dt>
                            <dd class="mt-2 leading-6 text-[#8b8b91]">{{ $selectedConversation->customer?->notes ?? 'No notes yet.' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        @endif
    </div>
</x-app-layout>
