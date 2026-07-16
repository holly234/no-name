<x-app-layout>
    @php
        $platforms = [
            ['key' => 'Instagram', 'label' => 'Instagram', 'demo' => true],
            ['key' => 'Facebook', 'label' => 'Facebook', 'demo' => true],
            ['key' => 'WhatsApp', 'label' => 'WhatsApp', 'demo' => false],
            ['key' => 'gmail', 'label' => 'Gmail', 'demo' => false],
            ['key' => 'Telegram', 'label' => 'Telegram', 'demo' => false],
        ];
        $accountsByPlatform = $accounts->groupBy('platform');

        $platformNotes = [
            'Instagram' => 'Direct inbox messages from connected Instagram business accounts.',
            'Facebook' => 'Messenger inbox conversations from connected Facebook Pages.',
            'WhatsApp' => 'WhatsApp inbox messages from connected Business numbers.',
            'Gmail' => 'Customer emails from connected Gmail inboxes.',
            'Telegram' => 'Telegram messages from customers who message your connected bot account.',
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#2563EB]">Channels</p>
                <h2 class="mt-2 text-3xl font-bold text-[#111827]">Connected channels</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Choose which private inboxes feed this workspace. Each platform can have more than one connected account.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[#E5E7EB] bg-white px-3 py-2 text-xs font-semibold text-[#6B7280]">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#10B981]">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
                Scoped to {{ $currentBusiness->name ?? 'this workspace' }}
            </span>
        </div>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                @endphp

                <div class="content-card p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="{{ $channel['class'] }} flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">{!! $channel['icon'] !!}</svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-[#111827]">{{ $platform }}</h3>
                                <p class="mt-1 text-xs font-semibold text-[#6B7280]">{{ $connectedCount }} connected {{ \Illuminate\Support\Str::plural('account', $connectedCount) }}</p>
                            </div>
                        </div>

                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full {{ $isConnected ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#EEF0F3] text-[#6B7280]' }} px-2.5 py-1 text-xs font-bold">
                            <span class="h-2 w-2 rounded-full {{ $isConnected ? 'bg-[#10B981]' : 'bg-[#6B7280]' }}"></span>
                            {{ $isConnected ? 'Live' : ($isDemoPlatform ? 'Demo' : 'Ready') }}
                        </span>
                    </div>

                    <p class="mt-4 min-h-[72px] text-sm leading-6 text-[#6B7280]">{{ $platformNotes[$platform] }}</p>

                    <div class="mt-4 space-y-2 rounded-xl bg-[#F5F6F8] p-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-semibold uppercase tracking-[0.14em] text-[#6B7280]">Latest account</span>
                            <span class="min-w-0 truncate text-right font-semibold text-[#111827]">{{ $account?->account_name ?? 'Waiting for connection' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-semibold uppercase tracking-[0.14em] text-[#6B7280]">Last connected</span>
                            <span class="font-semibold text-[#6B7280]">{{ $account?->connected_at?->diffForHumans() ?? 'Not yet' }}</span>
                        </div>
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
                                    class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                                >
                            </label>

                            <button class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
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
                            <button type="button" x-on:click="connect" x-bind:disabled="loading || !{{ $metaAppId ? 'true' : 'false' }} || !{{ $metaConfigId ? 'true' : 'false' }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!loading">Connect WhatsApp</span>
                                <span x-show="loading" x-cloak>Opening Meta setup...</span>
                            </button>
                            <p x-show="message" x-text="message" class="mt-2 text-xs font-semibold text-[#6B7280]"></p>
                            <p class="mt-2 text-xs leading-5 text-[#6B7280]">Connect securely through Meta. No access token is pasted or exposed here.</p>
                        </div>
                    @elseif ($platformKey === 'Telegram')
                        <form method="POST" action="{{ route('dashboard.accounts.telegram.connect') }}" class="mt-4 space-y-3">
                            @csrf
                            <label class="block">
                                <span class="sr-only">Telegram account display name</span>
                                <input
                                    type="text"
                                    name="account_name"
                                    maxlength="80"
                                    placeholder="Optional display name"
                                    class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                                >
                            </label>
                            <label class="block">
                                <span class="sr-only">Telegram bot username</span>
                                <input
                                    type="text"
                                    name="bot_username"
                                    maxlength="80"
                                    placeholder="Bot username, e.g. BrandSupportBot"
                                    class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                                >
                            </label>
                            <label class="block">
                                <span class="sr-only">Telegram bot token</span>
                                <input
                                    type="password"
                                    name="bot_token"
                                    maxlength="220"
                                    placeholder="Bot token from BotFather"
                                    class="w-full rounded-lg border border-[#E5E7EB] bg-white px-3 py-2.5 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                                >
                            </label>

                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 12a9 9 0 0 0-15.2-6.5L3 8" />
                                    <path d="M3 3v5h5" />
                                    <path d="M3 12a9 9 0 0 0 15.2 6.5L21 16" />
                                    <path d="M16 16h5v5" />
                                </svg>
                                {{ $isConnected ? 'Add another Telegram' : 'Connect Telegram' }}
                            </button>
                        </form>
                    @elseif ($platformKey === 'gmail' && $isConnected)
                        <div class="mt-4 grid gap-2">
                            <form method="POST" action="{{ route('dashboard.accounts.gmail.sync', $account) }}">
                                @csrf
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
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
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-pink-200 bg-pink-50 px-4 py-3 text-sm font-semibold text-[#BE185D] transition hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-200">
                                    Disconnect Gmail
                                </button>
                            </form>
                        </div>
                    @else
                        @if ($platformKey === 'gmail')
                        <a href="{{ route('dashboard.accounts.gmail.redirect') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2563EB] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                            Connect Gmail
                        </a>
                        @endif
                    @endif
                </div>
            @endforeach
        </section>

        <section class="content-card overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-[#E5E7EB] px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-[#111827]">Workspace channels</h3>
                    <p class="mt-1 text-sm text-[#6B7280]">{{ $accounts->count() }} active {{ \Illuminate\Support\Str::plural('channel', $accounts->count()) }}</p>
                </div>
            </div>

            @if ($accounts->isEmpty())
                <div class="p-6 text-sm leading-6 text-[#6B7280]">
                    No active channels yet. Connect a channel above to feed the workspace inbox.
                </div>
            @else
                <div class="divide-y divide-[#E5E7EB]">
                    @foreach ($accounts as $account)
                        @php
                            $channelLabel = $account->platform === 'gmail' ? 'Gmail' : $account->platform;
                            $channel = \App\Support\InboxUi::channelMeta($channelLabel);
                        @endphp

                        <div class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)_120px_130px_120px] sm:items-center">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="{{ $channel['class'] }} flex h-11 w-11 shrink-0 items-center justify-center rounded-xl">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">{!! $channel['icon'] !!}</svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-[#111827]">{{ $channelLabel }}</p>
                                    <p class="mt-1 truncate text-xs font-semibold text-[#6B7280]">{{ $account->external_account_id }}</p>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[#111827]">{{ $account->account_name }}</p>
                                <p class="mt-1 text-xs text-[#6B7280]">Feeds messages into the unified inbox.</p>
                            </div>

                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#ECFDF5] px-3 py-1 text-xs font-bold text-[#047857]">
                                <span class="h-2 w-2 rounded-full bg-[#10B981]"></span>
                                {{ $account->status }}
                            </span>

                            <p class="text-sm font-semibold text-[#6B7280] sm:text-right">{{ $account->connected_at?->diffForHumans() ?? 'Not connected' }}</p>

                            <div class="sm:text-right">
                                @if ($account->platform === 'gmail')
                                    <form method="POST" action="{{ route('dashboard.accounts.gmail.sync', $account) }}" class="mb-2">
                                        @csrf
                                        <button class="inline-flex w-fit items-center justify-center gap-2 rounded-lg border border-[#E5E7EB] bg-white px-3 py-2 text-xs font-semibold text-[#111827] transition hover:bg-[#F5F6F8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20">
                                            Sync
                                        </button>
                                    </form>
                                @elseif ($account->platform === 'Telegram')
                                    @php
                                        $webhookStatus = $account->provider_meta['webhook_status'] ?? 'not_registered';
                                        $webhookLastAttempt = $account->provider_meta['last_webhook_attempt_at'] ?? null;
                                        $webhookLastProcessed = $account->provider_meta['last_webhook_processed_at'] ?? null;
                                        $webhookLastError = $account->provider_meta['last_webhook_error'] ?? null;
                                        $webhookCopy = match ($webhookStatus) {
                                            'active' => 'Webhook live',
                                            'needs_public_https' => 'Needs public HTTPS URL',
                                            'network_failed' => 'Telegram unreachable',
                                            'failed' => 'Webhook failed',
                                            default => 'Webhook pending',
                                        };
                                    @endphp
                                    <div class="mb-2 space-y-1 text-xs font-semibold">
                                        <p class="{{ $webhookStatus === 'active' ? 'text-[#047857]' : 'text-[#B45309]' }}">{{ $webhookCopy }}</p>
                                        @if ($webhookLastProcessed)
                                            <p class="text-[#047857]">Last message received</p>
                                        @elseif ($webhookLastAttempt)
                                            <p class="text-[#B45309]">Telegram reached app, not processed</p>
                                        @else
                                            <p class="text-[#6B7280]">No Telegram delivery yet</p>
                                        @endif
                                        @if ($webhookLastError)
                                            <p class="max-w-[9rem] truncate text-[#BE185D]" title="{{ $webhookLastError }}">{{ $webhookLastError }}</p>
                                        @endif
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('dashboard.accounts.disconnect', $account) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="inline-flex w-fit items-center justify-center gap-2 rounded-lg border border-pink-200 bg-pink-50 px-3 py-2 text-xs font-semibold text-[#BE185D] transition hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-200">
                                        Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
