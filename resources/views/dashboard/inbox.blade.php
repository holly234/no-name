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
    @endphp

    <div x-data="{ profileOpen: false }" class="grid h-full min-h-0 w-full min-w-0 max-w-full grid-cols-[minmax(0,1fr)] overflow-hidden bg-[#F5F6F8] text-[#111827] lg:grid-cols-[410px_minmax(0,1fr)] xl:grid-cols-[410px_minmax(0,1fr)_320px]">
        <aside class="{{ $conversationIsOpen ? 'hidden' : 'flex' }} h-full min-h-0 w-full min-w-0 max-w-full flex-col overflow-hidden border-r border-[#E5E7EB] bg-white lg:flex">
            <div class="w-full max-w-full shrink-0 overflow-hidden border-b border-[#E5E7EB] bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="flex w-full min-w-0 items-center gap-2">
                    <button type="button" class="mobile-menu-button lg:hidden" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                        <span class="mobile-menu-mark" aria-hidden="true"></span>
                    </button>
                    <form method="GET" action="{{ route('dashboard.inbox') }}" class="min-w-0 flex-1">
                        <input type="hidden" name="state" value="{{ $activeState }}">
                        <input type="hidden" name="channel" value="{{ $activeChannel }}">
                        <label class="flex min-w-0 items-center gap-3 overflow-hidden rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] px-3 py-3 text-[#6B7280] transition focus-within:border-[#2563EB] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#2563EB]/15 sm:px-4">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0">
                                <path d="m21 21-4.35-4.35"></path>
                                <circle cx="11" cy="11" r="7"></circle>
                            </svg>
                            <span class="sr-only">Search conversations</span>
                            <input name="q" value="{{ $search }}" autocomplete="off" placeholder="Search conversations" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-medium text-[#111827] placeholder:text-[#6B7280] focus:ring-0">
                            @if ($search !== '')
                                <a href="{{ route('dashboard.inbox', array_filter(['state' => $activeState, 'channel' => $activeChannel === 'All' ? null : $activeChannel])) }}" class="shrink-0 rounded-full px-1.5 text-xs font-bold text-[#6B7280] hover:bg-[#EEF0F3] hover:text-[#111827]" aria-label="Clear search">x</a>
                            @endif
                        </label>
                    </form>
                </div>

                <div class="mt-3 grid w-full grid-cols-6 gap-1 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-1">
                    @foreach ($channelMeta as $channel => $meta)
                        @php
                            $isChannelActive = $activeChannel === $channel;
                            $channelUrl = route('dashboard.inbox', array_filter([
                                'state' => $activeState,
                                'channel' => $channel === 'All' ? null : $channel,
                                'q' => $search ?: null,
                            ]));
                        @endphp
                        <a href="{{ $channelUrl }}" title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}" class="group flex h-10 items-center justify-center rounded-lg transition {{ $isChannelActive ? 'bg-white shadow-sm ring-1 ring-[#E5E7EB]' : 'hover:bg-white' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg shadow-sm {{ $meta['class'] }} {{ $isChannelActive ? 'ring-2 ring-[#2563EB]/20' : 'opacity-80 group-hover:opacity-100' }}">
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
                                'state' => $state,
                                'channel' => $activeChannel === 'All' ? null : $activeChannel,
                                'q' => $search ?: null,
                            ]));
                        @endphp
                        <a href="{{ $stateUrl }}" title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}" class="group flex h-10 min-w-0 items-center justify-center gap-1 rounded-lg text-xs font-bold transition sm:h-11 {{ $isActive ? 'bg-[#2563EB] text-white shadow-sm' : 'text-[#6B7280] hover:bg-white hover:text-[#111827]' }}">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                                {!! $meta['icon'] !!}
                            </svg>
                            <span class="sr-only">{{ $meta['label'] }}</span>
                            <span>{{ $count }}</span>
                        </a>
                    @endforeach
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
                    @endphp
                    <a href="{{ route('dashboard.inbox', array_filter(['state' => $activeState, 'channel' => $activeChannel === 'All' ? null : $activeChannel, 'q' => $search ?: null, 'conversation' => $conversation->id])) }}" class="group block w-full min-w-0 max-w-full overflow-hidden px-4 transition hover:bg-[#F5F6F8] sm:px-5 {{ $selectedConversation?->id === $conversation->id ? 'bg-[#EFF6FF]' : '' }}">
                        <div class="flex w-full min-w-0 max-w-full gap-3 overflow-hidden border-b border-[#E5E7EB] py-3.5">
                            <span class="relative mt-1 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm sm:h-12 sm:w-12 {{ $channel['class'] }}" title="{{ $conversation->channel }}" aria-label="{{ $conversation->channel }}">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
                                    {!! $channel['icon'] !!}
                                </svg>
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
                @endphp
                <div class="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-[#E5E7EB] bg-white px-3 py-3 sm:px-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <a href="{{ route('dashboard.inbox', array_filter(['state' => $activeState, 'channel' => $activeChannel === 'All' ? null : $activeChannel, 'q' => $search ?: null])) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#374151] hover:bg-[#F5F6F8] lg:hidden" aria-label="Back to messages">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M15 18 9 12l6-6"></path>
                            </svg>
                        </a>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EEF0F3] text-sm font-bold text-[#111827]">{{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}</span>
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

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-[#F5F6F8] p-3 sm:p-5">
                    @foreach ($selectedConversation->messages as $message)
                        @php
                            $isGmailMessage = $selectedConversation->channel === 'Gmail' || ($message->metadata['source'] ?? null) === 'gmail';
                            $gmailSubject = $message->metadata['subject'] ?? null;
                            $gmailFrom = $message->metadata['from_email'] ?? $selectedConversation->customer_external_id;
                            $gmailTo = $message->metadata['to_email'] ?? null;
                            $gmailReplyDisabled = (bool) (($message->metadata['reply_disabled'] ?? false) || preg_match('/(^|[._+-])(no-?reply|do-?not-?reply|donotreply)([._+-]|@)/i', strtolower((string) $gmailFrom)));
                            $gmailReplyDisabledReason = $message->metadata['reply_disabled_reason'] ?? 'Automated sender';
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
                        @endphp
                        <div class="flex {{ $message->direction === 'outgoing' ? 'justify-end' : 'justify-start' }}">
                            <div class="{{ $isGmailMessage ? 'max-w-[94%] sm:max-w-[82%]' : 'max-w-[86%] sm:max-w-[72%]' }} rounded-2xl border px-4 py-3 text-sm shadow-sm {{ $message->direction === 'outgoing' ? 'border-[#BFDBFE] bg-[#EFF6FF] text-[#111827]' : 'border-[#E5E7EB] bg-white text-[#111827]' }}">
                                @if ($isGmailMessage)
                                    <div class="mb-3 border-b border-[#E5E7EB] pb-3">
                                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#ea4335]">
                                                <path d="M4 6h16v12H4z"></path>
                                                <path d="m4 7 8 6 8-6"></path>
                                            </svg>
                                            Email
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
                                    <p class="mb-1 text-xs font-bold uppercase {{ $message->sender_type === 'ai' ? 'text-[#047857]' : 'text-[#6B7280]' }}">{{ ucfirst($message->sender_type) }}</p>
                                    <div class="whitespace-pre-line break-words leading-6">{!! \App\Support\MessageText::linkify($messageBody) !!}</div>
                                @endif
                                @if ($message->attachments->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        @foreach ($message->attachments as $attachment)
                                            @php
                                                $isPdf = $attachment->mime_type === 'application/pdf';
                                                $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
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
                                            <a href="{{ route('dashboard.attachments.download', $attachment) }}" class="flex items-center gap-3 rounded-xl border border-[#E5E7EB] bg-[#F5F6F8] p-3 transition hover:bg-[#EEF0F3]">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $isPdf ? 'bg-pink-50 text-[#BE185D]' : ($isImage ? 'bg-[#EFF6FF] text-[#2563EB]' : 'bg-[#EEF0F3] text-[#374151]') }}">
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
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-1 flex justify-end gap-1 text-[11px] text-[#6B7280]">
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
                </div>

                <div class="shrink-0 border-t border-[#E5E7EB] bg-white p-3">
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
                        <form method="POST" action="{{ route('dashboard.inbox.reply', $selectedConversation) }}" enctype="multipart/form-data" class="space-y-2" data-human-on-submit="true" x-data="{ fileCount: 0, emojiOpen: false, automationPaused: @js($selectedConversation->ai_mode === 'human'), updateFiles() { this.fileCount = (this.$refs.fileInput?.files?.length || 0) + (this.$refs.imageInput?.files?.length || 0) + (this.$refs.audioInput?.files?.length || 0) }, insertEmoji(emoji) { const input = this.$refs.messageInput; const start = input.selectionStart ?? input.value.length; const end = input.selectionEnd ?? input.value.length; input.value = input.value.slice(0, start) + emoji + input.value.slice(end); input.focus(); input.selectionStart = input.selectionEnd = start + emoji.length; this.emojiOpen = false } }">
                            @csrf
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
                                    <textarea x-ref="messageInput" name="body" rows="1" class="min-w-[5rem] max-h-28 flex-1 resize-none border-0 bg-transparent py-3 text-sm text-[#111827] placeholder:text-[#6B7280] focus:ring-0" placeholder="Message"></textarea>
                                    <input x-ref="fileInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-attachments-{{ $selectedConversation->id }}" multiple class="hidden">
                                    <input x-ref="imageInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-images-{{ $selectedConversation->id }}" multiple accept="image/*" class="hidden">
                                    <input x-ref="audioInput" x-on:change="updateFiles" type="file" name="attachments[]" id="message-audio-{{ $selectedConversation->id }}" accept="audio/*" class="hidden">
                                    <label for="message-attachments-{{ $selectedConversation->id }}" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Attach file" title="Attach file">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                            <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 1 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                        </svg>
                                    </label>
                                    <label for="message-images-{{ $selectedConversation->id }}" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Upload image" title="Upload image">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                            <rect x="3" y="5" width="18" height="14" rx="2.5"></rect>
                                            <circle cx="8.5" cy="10" r="1.5"></circle>
                                            <path d="m21 15-4.5-4.5L8 19"></path>
                                        </svg>
                                    </label>
                                    <label for="message-audio-{{ $selectedConversation->id }}" class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-[#6B7280] transition hover:bg-white hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Upload voice note" title="Upload voice note">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                            <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Z"></path>
                                            <path d="M19 11a7 7 0 0 1-14 0"></path>
                                            <path d="M12 18v3"></path>
                                        </svg>
                                    </label>
                                    @if (! $aiSettings->human_takeover_enabled)
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[#9CA3AF] sm:h-9 sm:w-9" aria-label="Pause automation unavailable" title="Pause automation unavailable">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <path d="M10 5v14"></path>
                                                <path d="M14 5v14"></path>
                                            </svg>
                                        </span>
                                    @else
                                        <button x-show="! automationPaused" x-on:click="automationPaused = true" formaction="{{ route('dashboard.inbox.take-over', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#EFF6FF] text-[#2563EB] transition hover:bg-[#DBEAFE] sm:h-9 sm:w-9" aria-label="Automation active. Pause automation." title="Automation active">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <path d="M10 5v14"></path>
                                                <path d="M14 5v14"></path>
                                            </svg>
                                        </button>
                                        <button x-cloak x-show="automationPaused" x-on:click="automationPaused = false" formaction="{{ route('dashboard.inbox.resume-ai', $selectedConversation) }}" formnovalidate class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[#9CA3AF] transition hover:bg-[#EFF6FF] hover:text-[#2563EB] sm:h-9 sm:w-9" aria-label="Automation paused. Resume automation." title="Automation paused">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                <path d="M7.5 5.7c0-.9 1-1.4 1.8-.9l8.6 5.3c.7.4.7 1.4 0 1.8l-8.6 5.3c-.8.5-1.8-.1-1.8-.9V5.7Z"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                <button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#2563EB] text-white shadow-sm transition hover:bg-[#1d4ed8]" aria-label="Send message">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                                        <path d="M22 2 11 13"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 px-1">
                                <span x-show="fileCount > 0" x-cloak class="inline-flex items-center gap-2 rounded-full bg-[#EEF0F3] px-3 py-2 text-xs font-semibold text-[#6B7280]">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                    <span x-text="fileCount === 1 ? '1 file selected' : `${fileCount} files selected`"></span>
                                </span>
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
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EEF0F3] text-sm font-bold text-[#111827]">{{ \Illuminate\Support\Str::of($selectedConversation->customer_name)->substr(0, 1) }}</span>
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
