<x-app-layout>
    @php
        $platforms = [
            ['key' => 'Instagram', 'label' => 'Instagram', 'demo' => false],
            ['key' => 'Facebook', 'label' => 'Facebook', 'demo' => false],
            ['key' => 'WhatsApp', 'label' => 'WhatsApp', 'demo' => false],
            ['key' => 'gmail', 'label' => 'Gmail', 'demo' => false],
            ['key' => 'Telegram', 'label' => 'Telegram', 'demo' => false],
        ];
        $accountsByPlatform = $accounts->groupBy('platform');
        $connectedChannelCount = collect($platforms)->filter(fn ($platform) => $accountsByPlatform->get($platform['key'], collect())->isNotEmpty())->count();
        $needsSetupCount = collect($platforms)->filter(fn ($platform) => $accountsByPlatform->get($platform['key'], collect())->isEmpty())->count();
        $totalAccountCount = $accounts->count();

        $platformNotes = [
            'Instagram' => 'DMs, comments and story replies',
            'Facebook' => 'Comments, messages and mentions',
            'WhatsApp' => 'Business chats and notifications',
            'Gmail' => 'Email conversations via Gmail',
            'Telegram' => 'Messages and channel replies',
        ];
        $metaDevelopmentPlatforms = [
            'WhatsApp' => 'WhatsApp',
            'Facebook' => 'Facebook Messenger',
            'Instagram' => 'Instagram',
        ];
    @endphp

    <div class="space-y-6" x-data="{ accountFilter: 'all', accountFilterOpen: false, accountFilterLabel: 'All channels' }">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-[-0.04em] text-white">Connected channels</h2>
            </div>
        </div>

        <div class="relative md:hidden" x-on:click.outside="accountFilterOpen = false">
            <button
                type="button"
                x-on:click="accountFilterOpen = ! accountFilterOpen"
                class="inline-flex h-12 w-full items-center justify-between rounded-2xl border border-[#ffffff14] bg-[#151517] px-4 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0"
                x-bind:aria-expanded="accountFilterOpen"
            >
                <span x-text="accountFilterLabel"></span>
                <svg class="h-4 w-4 text-[#b9bbc3] transition" x-bind:class="{ 'rotate-180': accountFilterOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>

            <div x-cloak x-show="accountFilterOpen" x-transition.origin.top class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-2xl border border-[#ffffff14] bg-[#151517] p-1 shadow-2xl">
                <button type="button" x-on:click="accountFilter = 'all'; accountFilterLabel = 'All channels'; accountFilterOpen = false" class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm font-semibold transition hover:bg-[#0e0e10]" x-bind:class="accountFilter === 'all' ? 'text-white' : 'text-[#8b8b91]'">
                    <span>All channels</span>
                    <svg x-show="accountFilter === 'all'" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                </button>
                <button type="button" x-on:click="accountFilter = 'workspace'; accountFilterLabel = 'Workspace'; accountFilterOpen = false" class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm font-semibold transition hover:bg-[#0e0e10]" x-bind:class="accountFilter === 'workspace' ? 'text-white' : 'text-[#8b8b91]'">
                    <span>Workspace</span>
                    <svg x-show="accountFilter === 'workspace'" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                </button>
                @foreach ($platforms as $platformConfig)
                    <button type="button" x-on:click="accountFilter = @js($platformConfig['key']); accountFilterLabel = @js($platformConfig['label']); accountFilterOpen = false" class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm font-semibold transition hover:bg-[#0e0e10]" x-bind:class="accountFilter === @js($platformConfig['key']) ? 'text-white' : 'text-[#8b8b91]'">
                        <span>{{ $platformConfig['label'] }}</span>
                        <svg x-show="accountFilter === @js($platformConfig['key'])" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                    </button>
                @endforeach
            </div>
        </div>

        <section class="grid overflow-hidden rounded-2xl border border-[#ffffff14] bg-[#151517] md:grid-cols-3">
            <div class="flex items-center gap-4 p-5 md:border-r md:border-[#ffffff14]">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0e0e10] text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                </span>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($connectedChannelCount) }}</p>
                    <p class="mt-1 text-sm text-[#8b8b91]">Connected channels</p>
                </div>
            </div>
            <div class="flex items-center gap-4 border-t border-[#ffffff14] p-5 md:border-r md:border-t-0 md:border-[#ffffff14]">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0e0e10] text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M9 7h1M14 7h1M9 12h1M14 12h1M9 17h1M14 17h1"></path></svg>
                </span>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($totalAccountCount) }}</p>
                    <p class="mt-1 text-sm text-[#8b8b91]">Total accounts</p>
                </div>
            </div>
            <div class="flex items-center gap-4 border-t border-[#ffffff14] p-5 md:border-t-0">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0e0e10] text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                </span>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($needsSetupCount) }}</p>
                    <p class="mt-1 text-sm text-[#8b8b91]">Needs setup</p>
                </div>
            </div>
        </section>

        <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_30rem]">
        <section x-show="accountFilter !== 'workspace'" class="space-y-2.5">
            @foreach ($platforms as $platformConfig)
                @php
                    $platformKey = $platformConfig['key'];
                    $platform = $platformConfig['label'];
                    $isDemoPlatform = $platformConfig['demo'];
                    $platformAccounts = $accountsByPlatform->get($platformKey, collect());
                    $account = $platformAccounts->first();
                    $connectedCount = $platformAccounts->count();
                    $channel = \App\Support\InboxUi::channelMeta($platform);
                    $isConnected = $connectedCount > 0;
                    $latestAccountLabel = $account?->account_name ?? ($isConnected ? 'Connected account' : 'Connect your '.$platform.' account');
                @endphp

                <div x-show="accountFilter === 'all' || accountFilter === @js($platformKey)" class="account-tile grid items-center gap-3 rounded-2xl p-3.5 md:min-h-[5.5rem] md:grid-cols-[minmax(18rem,1.45fr)_9.5rem_7rem_minmax(14rem,1fr)_12rem] md:p-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="{{ $channel['class'] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">{!! $channel['icon'] !!}</svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-white">{{ $platform }}</h3>
                            <p class="mt-1 truncate text-sm text-[#8b8b91]">{{ $platformNotes[$platform] }}</p>
                        </div>
                    </div>

                    <span class="inline-flex w-fit shrink-0 items-center gap-2 justify-self-start rounded-full border {{ $isConnected ? 'border-[#10B981]/30 bg-[#10B981]/10 text-[#74f59a]' : 'border-[#ffffff14] bg-[#0e0e10] text-[#8b8b91]' }} px-3 py-1.5 text-xs font-bold">
                        <span class="h-2 w-2 rounded-full {{ $isConnected ? 'bg-[#10B981]' : 'bg-[#8b8b91]' }}"></span>
                        {{ $isConnected ? 'Connected' : ($isDemoPlatform ? 'Demo' : ($platformKey === 'gmail' ? 'Needs setup' : 'Not connected')) }}
                    </span>

                    <p class="text-sm font-semibold text-[#b9bbc3]">{{ $connectedCount }} {{ \Illuminate\Support\Str::plural('account', $connectedCount) }}</p>

                    <div class="min-w-0">
                        <p class="text-xs font-medium text-[#8b8b91]">{{ $isConnected ? 'Latest account' : '' }}</p>
                        <p class="mt-1 truncate text-sm font-semibold {{ $isConnected ? 'text-white' : 'text-[#8b8b91]' }}">{{ $latestAccountLabel }}</p>
                        @if ($platformKey === 'gmail' && ($account?->provider_meta['last_sync_error'] ?? null))
                            <div class="account-tile-note mt-2 rounded-lg px-3 py-2 text-xs text-[#8b8b91]">
                                <span class="block font-semibold uppercase tracking-[0.14em] text-[#6B7280]">Last sync error</span>
                                <span class="mt-1 block break-words">{{ $account->provider_meta['last_sync_error'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($isDemoPlatform)
                        <form method="POST" action="{{ route('dashboard.accounts.fake-connect') }}">
                            @csrf
                            <input type="hidden" name="platform" value="{{ $platform }}">

                            <label class="mt-4 block">
                                <span class="sr-only">{{ $platform }} account display name</span>
                                <input
                                    type="text"
                                    name="account_name"
                                    maxlength="80"
                                    placeholder="Optional display name"
                                    class="w-full rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-3 py-2.5 text-sm font-medium text-white placeholder:text-[#8b8b91] focus:border-[#ffffff14] focus:outline-none focus:ring-0"
                                >
                            </label>

                            <button class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#151517] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 12a9 9 0 0 0-15.2-6.5L3 8" />
                                    <path d="M3 3v5h5" />
                                    <path d="M3 12a9 9 0 0 0 15.2 6.5L21 16" />
                                    <path d="M16 16h5v5" />
                                </svg>
                                {{ $isConnected ? 'Add another '.$platform : 'Connect '.$platform }}
                            </button>
                        </form>
                    @elseif ($platformKey === 'WhatsApp')
                        <div class="mt-4" x-data="metaEmbeddedSignup(@js($metaAppId), @js($metaConfigId), @js($metaGraphVersion), @js(route('dashboard.accounts.whatsapp.embedded-signup')), @js(csrf_token()), @js($metaSignupNonce))">
                            <button type="button" x-on:click="connect" x-bind:disabled="loading || !sdkReady || !{{ $metaAppId ? 'true' : 'false' }} || !{{ $metaConfigId ? 'true' : 'false' }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#151517] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!loading && sdkReady">Connect WhatsApp</span>
                                <span x-show="!loading && !sdkReady" x-cloak>Preparing Meta...</span>
                                <span x-show="loading" x-cloak>Opening Meta setup...</span>
                            </button>
                            <p x-show="message" x-text="message" class="mt-2 text-xs font-semibold text-[#6B7280]"></p>
                            <p class="mt-2 text-xs leading-5 text-[#6B7280]">Connect securely through Meta. No access token is pasted or exposed here.</p>
                        </div>
                    @elseif ($platformKey === 'Telegram')
                        <div class="account-row-action relative" x-data="{ telegramOpen: false }">
                            <button type="button" x-on:click="telegramOpen = !telegramOpen" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#151517] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 12a9 9 0 0 0-15.2-6.5L3 8" />
                                    <path d="M3 3v5h5" />
                                    <path d="M3 12a9 9 0 0 0 15.2 6.5L21 16" />
                                    <path d="M16 16h5v5" />
                                </svg>
                                {{ $isConnected ? 'Add another' : 'Connect' }}
                            </button>

                            <form
                                method="POST"
                                action="{{ route('dashboard.accounts.telegram.connect') }}"
                                x-cloak
                                x-show="telegramOpen"
                                x-transition
                                x-on:click.outside="telegramOpen = false"
                                class="absolute right-0 top-[calc(100%+0.5rem)] z-30 grid w-[min(38rem,calc(100vw-2rem))] gap-2 rounded-2xl border border-[#ffffff14] bg-[#151517] p-3 shadow-2xl md:grid-cols-[repeat(3,minmax(0,1fr))_auto]"
                            >
                                @csrf
                                <label class="block">
                                    <span class="sr-only">Telegram account display name</span>
                                    <input
                                        type="text"
                                        name="account_name"
                                        maxlength="80"
                                        placeholder="Optional display name"
                                        class="w-full rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-3 py-2.5 text-sm font-medium text-white placeholder:text-[#8b8b91] focus:border-[#ffffff14] focus:outline-none focus:ring-0"
                                    >
                                </label>
                                <label class="block">
                                    <span class="sr-only">Telegram bot username</span>
                                    <input
                                        type="text"
                                        name="bot_username"
                                        maxlength="80"
                                        placeholder="Bot username"
                                        class="w-full rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-3 py-2.5 text-sm font-medium text-white placeholder:text-[#8b8b91] focus:border-[#ffffff14] focus:outline-none focus:ring-0"
                                    >
                                </label>
                                <label class="block">
                                    <span class="sr-only">Telegram bot token</span>
                                    <input
                                        type="password"
                                        name="bot_token"
                                        maxlength="220"
                                        placeholder="Bot token"
                                        class="w-full rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-3 py-2.5 text-sm font-medium text-white placeholder:text-[#8b8b91] focus:border-[#ffffff14] focus:outline-none focus:ring-0"
                                    >
                                </label>

                                <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-4 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                                    Connect
                                </button>
                            </form>
                        </div>
                    @elseif ($platformKey === 'gmail' && $isConnected)
                        <div class="mt-4 grid gap-2">
                            <form method="POST" action="{{ route('dashboard.accounts.gmail.sync', $account) }}">
                                @csrf
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#151517] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M21 12a9 9 0 0 0-15.2-6.5L3 8" />
                                        <path d="M3 3v5h5" />
                                        <path d="M3 12a9 9 0 0 0 15.2 6.5L21 16" />
                                        <path d="M16 16h5v5" />
                                    </svg>
                                    Sync emails
                                </button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.accounts.disconnect', $account) }}">
                                @csrf
                                @method('PATCH')
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                                    Disconnect Gmail
                                </button>
                            </form>
                        </div>
                    @else
                        @if ($platformKey === 'gmail')
                        <a href="{{ route('dashboard.accounts.gmail.redirect') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#151517] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#151517] focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                            Connect Gmail
                        </a>
                        @else
                            <span class="hidden md:block" aria-hidden="true"></span>
                        @endif
                    @endif
                </div>
            @endforeach
        </section>

        <section x-show="accountFilter === 'all' || accountFilter === 'workspace'" class="content-card h-fit overflow-hidden 2xl:sticky 2xl:top-[5.5rem]">
            <div class="flex flex-col gap-2 border-b border-[#ffffff14] px-4 py-4">
                <h3 class="text-base font-bold text-white">Workspace channels</h3>
                <p class="text-sm text-[#8b8b91]">{{ $accounts->count() }} active {{ \Illuminate\Support\Str::plural('channel', $accounts->count()) }}</p>
            </div>

            @if ($accounts->isEmpty())
                <div class="p-4 text-sm leading-6 text-[#8b8b91]">
                    No active channels yet. Connect a channel to feed the workspace inbox.
                </div>
            @else
                <div class="divide-y divide-[#ffffff14]">
                    @foreach ($accounts as $account)
                        @php
                            $channelLabel = $account->platform === 'gmail' ? 'Gmail' : $account->platform;
                            $channel = \App\Support\InboxUi::channelMeta($channelLabel);
                        @endphp

                        <div class="px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="{{ $channel['class'] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">{!! $channel['icon'] !!}</svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-white">{{ $account->account_name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-[#8b8b91]">{{ $channelLabel }} · {{ $account->connected_at?->diffForHumans() ?? 'Connected' }}</p>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#10B981]/10 px-2.5 py-1 text-xs font-bold text-[#74f59a]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#10B981]"></span>
                                    {{ $account->status }}
                                </span>
                                @if ($account->platform === 'gmail')
                                    <form method="POST" action="{{ route('dashboard.accounts.gmail.sync', $account) }}">
                                        @csrf
                                        <button class="inline-flex w-fit items-center justify-center rounded-lg border border-[#ffffff14] bg-[#151517] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#0e0e10]">Sync</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
        </div>

        @if ($metaDevelopmentConnectEnabled)
            <section
                class="content-card overflow-hidden"
                x-data="{
                    platform: @js(old('platform', 'WhatsApp')),
                    platformOpen: false,
                    platformOptions: @js($metaDevelopmentPlatforms),
                    get platformLabel() {
                        return this.platformOptions[this.platform] || 'Select channel';
                    },
                    choosePlatform(value) {
                        this.platform = value;
                        this.platformOpen = false;
                    },
                }"
                x-on:keydown.escape.window="platformOpen = false"
            >
                <div class="border-b border-[#ffffff14] px-4 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-bold text-white">Meta development connection</h3>
                        <span class="rounded-full bg-[#151517] px-2.5 py-1 text-xs font-bold text-white">Testing only</span>
                    </div>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-[#8b8b91]">Connect assets owned by Meta app administrators, developers, or testers while public OAuth approval is pending. Tokens are encrypted and never shown again.</p>
                </div>

                <form method="POST" action="{{ route('dashboard.accounts.meta.development-connect') }}" class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                    @csrf
                    <div class="block">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-[#8b8b91]">Channel</span>
                        <div class="relative" x-on:click.outside="platformOpen = false">
                            <input type="hidden" name="platform" x-bind:value="platform">
                            <button
                                type="button"
                                x-on:click="platformOpen = ! platformOpen"
                                class="inline-flex w-full items-center justify-between gap-3 rounded-lg border border-[#ffffff14] bg-[#0e0e10] px-3 py-2.5 text-left text-sm font-semibold text-white shadow-sm transition hover:bg-[#151517] focus:border-[#ffffff14] focus:outline-none focus:ring-0"
                                aria-haspopup="listbox"
                                x-bind:aria-expanded="platformOpen"
                            >
                                <span class="truncate" x-text="platformLabel"></span>
                                    <svg class="h-4 w-4 shrink-0 text-[#8b8b91] transition" x-bind:class="{ 'rotate-180': platformOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>

                            <div x-cloak x-show="platformOpen" x-transition.origin.top class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-[#ffffff14] bg-[#0e0e10] py-1 shadow-xl shadow-[#0e0e10]/20" role="listbox">
                                @foreach ($metaDevelopmentPlatforms as $value => $label)
                                    <button
                                        type="button"
                                        x-on:click="choosePlatform(@js($value))"
                                        class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-[#151517] hover:text-white"
                                        x-bind:class="platform === @js($value) ? 'bg-[#151517] text-white' : 'text-[#8b8b91]'"
                                        role="option"
                                        x-bind:aria-selected="platform === @js($value)"
                                    >
                                        <span>{{ $label }}</span>
                                        <svg x-cloak x-show="platform === @js($value)" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Display name</span>
                        <input type="text" name="account_name" value="{{ old('account_name') }}" maxlength="80" placeholder="Optional internal label" class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm text-[#111827] focus:border-[#2563EB] focus:ring-[#2563EB]/20">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]" x-text="platform === 'WhatsApp' ? 'Phone Number ID' : (platform === 'Facebook' ? 'Facebook Page ID' : 'Instagram Professional Account ID')"></span>
                        <input type="text" name="asset_id" value="{{ old('asset_id') }}" maxlength="100" placeholder="Numeric Meta asset ID" required class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm text-[#111827] focus:border-[#2563EB] focus:ring-[#2563EB]/20">
                    </label>

                    <label class="block" x-show="platform === 'WhatsApp'" x-cloak>
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">WhatsApp Business Account ID</span>
                        <input type="text" name="business_account_id" value="{{ old('business_account_id') }}" maxlength="100" placeholder="WABA ID" x-bind:required="platform === 'WhatsApp'" class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm text-[#111827] focus:border-[#2563EB] focus:ring-[#2563EB]/20">
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">Access token</span>
                        <input type="password" name="access_token" maxlength="4096" autocomplete="off" placeholder="Temporary or system-user/Page token" required class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm text-[#111827] focus:border-[#2563EB] focus:ring-[#2563EB]/20">
                    </label>

                    <label class="flex items-center gap-3 rounded-lg bg-[#F5F6F8] px-3 py-2.5 text-sm font-semibold text-[#111827]">
                        <input type="hidden" name="subscribe_webhooks" value="0">
                        <input type="checkbox" name="subscribe_webhooks" value="1" checked class="rounded border-[#D1D5DB] text-[#2563EB] focus:ring-[#2563EB]/30">
                        Subscribe this asset to the webhook
                    </label>

                    <div class="flex items-end md:col-span-2 xl:col-span-3">
                        <button class="inline-flex w-full items-center justify-center rounded-lg bg-[#111827] px-4 py-3 text-sm font-bold text-white transition hover:bg-black sm:w-auto">Validate and connect test asset</button>
                    </div>
                </form>
            </section>
        @endif

    </div>
</x-app-layout>
