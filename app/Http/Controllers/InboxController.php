<?php

namespace App\Http\Controllers;

use App\Models\AutomationLog;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationMessageService;
use App\Services\GmailConnectionService;
use App\Services\TelegramConnectionService;
use App\Services\MetaConnectionService;
use App\Support\InboxUi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    private const CONVERSATION_PAGE_SIZE = 50;
    private const MESSAGE_HISTORY_LIMIT = 100;

    public function index(Request $request, ConversationMessageService $conversationMessageService)
    {
        $business = $request->attributes->get('currentBusiness');
        $user = $request->user();
        $aiSettings = AiSetting::firstOrCreate(['business_id' => $business->id]);
        $activeState = $request->query('state', 'All');
        $search = trim((string) $request->query('q', ''));
        $activeChannel = $request->query('channel', 'All');
        $activeDate = $request->query('date', 'all');
        $activeTime = $request->query('time', 'all');
        $activeSort = $request->query('sort', 'newest');
        $allowedChannels = ['All', 'Instagram', 'WhatsApp', 'Facebook', 'Gmail', 'Telegram'];
        $allowedDates = ['all', 'today', 'yesterday', '7d', '30d'];
        $allowedTimes = ['all', 'morning', 'afternoon', 'evening', 'night'];
        $allowedSorts = ['newest', 'oldest'];

        if (! in_array($activeChannel, $allowedChannels, true)) {
            $activeChannel = 'All';
        }

        if (! in_array($activeDate, $allowedDates, true)) {
            $activeDate = 'all';
        }

        if (! in_array($activeTime, $allowedTimes, true)) {
            $activeTime = 'all';
        }

        if (! in_array($activeSort, $allowedSorts, true)) {
            $activeSort = 'newest';
        }

        $selectedId = $request->integer('conversation');
        $conversationIsOpen = $request->has('conversation');

        $statusCounts = Conversation::where('business_id', $business->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['All' => (int) $statusCounts->sum()];
        foreach (Conversation::STATES as $state) {
            $counts[$state] = (int) ($statusCounts[$state] ?? 0);
        }

        $conversationQuery = Conversation::with([
            'latestMessage',
            'customer',
            'reads' => fn ($query) => $query->where('user_id', $user->id),
        ])
            ->where('business_id', $business->id);

        if (in_array($activeState, Conversation::STATES, true)) {
            $conversationQuery->where('status', $activeState);
        }

        if ($activeChannel !== 'All') {
            $conversationQuery->where('channel', $activeChannel);
        }

        if ($search !== '') {
            $conversationQuery->where(function ($query) use ($search) {
                $query
                    ->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_external_id', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%")
                    ->orWhereHas('messages', function ($messageQuery) use ($search) {
                        $messageQuery
                            ->where('body', 'like', "%{$search}%")
                            ->orWhere('metadata->subject', 'like', "%{$search}%")
                            ->orWhere('metadata->from_email', 'like', "%{$search}%")
                            ->orWhere('metadata->to_email', 'like', "%{$search}%")
                            ->orWhereHas('attachments', function ($attachmentQuery) use ($search) {
                                $attachmentQuery->where('filename', 'like', "%{$search}%");
                            });
                    });
            });
        }

        match ($activeDate) {
            'today' => $conversationQuery->whereDate('last_message_at', today()),
            'yesterday' => $conversationQuery->whereBetween('last_message_at', [
                today()->subDay()->startOfDay(),
                today()->subDay()->endOfDay(),
            ]),
            '7d' => $conversationQuery->where('last_message_at', '>=', now()->subDays(7)),
            '30d' => $conversationQuery->where('last_message_at', '>=', now()->subDays(30)),
            default => null,
        };

        match ($activeTime) {
            'morning' => $conversationQuery->whereTime('last_message_at', '>=', '06:00:00')->whereTime('last_message_at', '<', '12:00:00'),
            'afternoon' => $conversationQuery->whereTime('last_message_at', '>=', '12:00:00')->whereTime('last_message_at', '<', '17:00:00'),
            'evening' => $conversationQuery->whereTime('last_message_at', '>=', '17:00:00')->whereTime('last_message_at', '<', '21:00:00'),
            'night' => $conversationQuery->where(function ($query) {
                $query->whereTime('last_message_at', '>=', '21:00:00')
                    ->orWhereTime('last_message_at', '<', '06:00:00');
            }),
            default => null,
        };

        $sortDirection = $activeSort === 'oldest' ? 'asc' : 'desc';
        $conversationPage = $conversationQuery
            ->orderBy('last_message_at', $sortDirection)
            ->orderBy('id', $sortDirection)
            ->cursorPaginate(self::CONVERSATION_PAGE_SIZE)
            ->withQueryString();
        $conversations = $conversationPage->getCollection();

        $selectedConversation = null;
        if ($selectedId) {
            $selectedConversation = Conversation::with([
                'customer',
                'connectedAccount',
                'latestMessage',
                'reads' => fn ($query) => $query->where('user_id', $user->id),
            ])
                ->where('business_id', $business->id)
                ->where('id', $selectedId)
                ->first();
        }

        if ($selectedConversation) {
            $messages = $selectedConversation->messages()
                ->with('attachments')
                ->latest()
                ->limit(self::MESSAGE_HISTORY_LIMIT)
                ->get()
                ->reverse()
                ->values();

            $selectedConversation->setRelation('messages', $messages);

            if ($conversationIsOpen) {
                $conversationMessageService->markReadForUser($selectedConversation, $user->id);
            }
        }

        $conversations->each(function (Conversation $conversation) use ($selectedConversation, $conversationIsOpen) {
            $lastReadAt = $conversation->reads->first()?->last_read_at;

            if ($conversationIsOpen && $selectedConversation?->id === $conversation->id) {
                $lastReadAt = now();
            }

            $conversation->setAttribute('unread_count', $this->unreadCount($conversation, $lastReadAt));
            $conversation->setAttribute('intent', InboxUi::intentFor($conversation));
            $conversation->setAttribute('status_meta', InboxUi::statusMeta($conversation->status));
            $conversation->setAttribute('channel_meta', InboxUi::channelMeta($conversation->channel));
            $conversation->setAttribute('reply_disabled', $conversation->latestMessage
                ? $conversation->messageDisablesReplies($conversation->latestMessage)
                : false);
        });

        if ($selectedConversation) {
            $selectedConversation->setAttribute('detected_intent', InboxUi::intentFor($selectedConversation));
        }

        return view('dashboard.inbox', [
            'activeState' => $activeState,
            'activeChannel' => $activeChannel,
            'activeDate' => $activeDate,
            'activeTime' => $activeTime,
            'activeSort' => $activeSort,
            'counts' => $counts,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'conversationIsOpen' => $conversationIsOpen,
            'search' => $search,
            'filterMeta' => InboxUi::stateFilters(),
            'channelMeta' => InboxUi::channels(),
            'nextConversationCursor' => $conversationPage->nextCursor()?->encode(),
            'messageHistoryLimit' => self::MESSAGE_HISTORY_LIMIT,
            'aiSettings' => $aiSettings,
            'inboxVersion' => $this->inboxVersion($business->id),
        ]);
    }

    public function reply(
        Request $request,
        Conversation $conversation,
        ConversationMessageService $conversationMessageService,
        GmailConnectionService $gmailConnectionService,
        TelegramConnectionService $telegramConnectionService
        , MetaConnectionService $metaConnectionService
    )
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($conversation->business_id === $business->id, 403);
        $conversation->loadMissing('messages');

        if ($conversation->replyDisabled()) {
            return back()->with('error', 'Replies are disabled for this email thread because it looks automated or not replyable.');
        }

        $validated = $request->validate([
            'body' => ['nullable', 'required_without:attachments', 'string', 'max:4000'],
            'reply_to_message_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,ppt,pptx,zip,mp3,wav,ogg,m4a,mp4,mov,webm'],
        ]);

        $attachments = $request->file('attachments', []);
        $replyTo = null;

        if ($conversation->channel === 'Gmail' && count($attachments) > 0) {
            return back()->with('error', 'Gmail file replies are not enabled yet. Send a text reply for this email thread.');
        }

        if (! empty($validated['reply_to_message_id'])) {
            $replyTo = Message::query()
                ->where('business_id', $business->id)
                ->where('conversation_id', $conversation->id)
                ->where('id', $validated['reply_to_message_id'])
                ->first();
        }

        $replyMetadata = [];

        if ($replyTo) {
            $replyBody = trim((string) $replyTo->body);

            $replyMetadata['reply_to'] = [
                'id' => $replyTo->id,
                'sender' => $replyTo->direction === 'incoming'
                    ? $conversation->customer_name
                    : ($replyTo->sender_type === 'human' ? ($request->user()?->name ?? 'Team') : ucfirst($replyTo->sender_type)),
                'body' => $replyBody !== '' ? str($replyBody)->limit(120)->toString() : 'Attachment',
            ];
        }

        $message = $conversationMessageService->saveOutgoing(
            $conversation,
            trim((string) ($validated['body'] ?? '')),
            'human',
            metadata: $replyMetadata,
            attachments: $attachments,
        );
        $deliveryMeta = [];

        if ($conversation->channel === 'Telegram' && ($message->body !== '' || $message->attachments()->exists())) {
            try {
                $telegramConversation = $conversation->fresh('connectedAccount');
                $telegramResponse = null;
                $attachmentResponses = [];

                if ($message->body !== '') {
                    $telegramResponse = $telegramConnectionService->sendTextMessage($telegramConversation, $message->body);
                }

                foreach ($message->attachments as $attachment) {
                    $attachmentResponses[] = $telegramConnectionService->sendAttachment($telegramConversation, $attachment);
                }

                $deliveryMeta = [
                    'telegram_response' => $telegramResponse,
                    'telegram_message_id' => $telegramResponse['result']['message_id'] ?? null,
                    'telegram_chat_id' => $telegramResponse['result']['chat']['id'] ?? $conversation->customer_external_id,
                    'telegram_attachment_responses' => $attachmentResponses,
                ];
            } catch (ConnectionException $exception) {
                report($exception);

                $this->logReplyFailure($business->id, $conversation, 'Telegram connection failed while sending reply.', [
                    'error' => $exception->getMessage(),
                ]);

                return back()->with('error', 'Reply saved locally, but Telegram could not be reached from this machine.');
            } catch (RequestException $exception) {
                report($exception);

                $response = $exception->response?->json();
                $telegramReason = $response['description'] ?? $exception->getMessage();

                $this->logReplyFailure($business->id, $conversation, 'Telegram rejected the reply.', [
                    'telegram_response' => $response,
                    'http_status' => $exception->response?->status(),
                ]);

                return back()->with('error', 'Reply saved locally, but Telegram rejected it: '.$telegramReason);
            } catch (\Throwable $exception) {
                report($exception);

                $this->logReplyFailure($business->id, $conversation, 'Telegram reply failed unexpectedly.', [
                    'error' => $exception->getMessage(),
                ]);

                return back()->with('error', 'Reply saved locally, but Telegram did not confirm delivery: '.$exception->getMessage());
            }
        }

        if ($conversation->channel === 'Gmail' && $message->body !== '') {
            try {
                $gmailConversation = $conversation->fresh('connectedAccount');
                $gmailResponse = $gmailConnectionService->sendReply($gmailConversation, $message);

                $deliveryMeta = [
                    'gmail_response' => $gmailResponse,
                    'gmail_message_id' => $gmailResponse['id'] ?? null,
                    'gmail_thread_id' => $gmailResponse['threadId'] ?? null,
                    'gmail_label_ids' => $gmailResponse['labelIds'] ?? null,
                ];
            } catch (ConnectionException $exception) {
                report($exception);

                $this->logReplyFailure($business->id, $conversation, 'Gmail connection failed while sending reply.', [
                    'error' => $exception->getMessage(),
                ]);

                return back()->with('error', 'Reply saved locally, but Gmail could not be reached from this machine.');
            } catch (RequestException $exception) {
                report($exception);

                $response = $exception->response?->json();
                $gmailReason = $response['error']['message'] ?? $exception->getMessage();

                $this->logReplyFailure($business->id, $conversation, 'Gmail rejected the reply.', [
                    'gmail_response' => $response,
                    'http_status' => $exception->response?->status(),
                ]);

                return back()->with('error', 'Reply saved locally, but Gmail rejected it: '.$gmailReason);
            } catch (\Throwable $exception) {
                report($exception);

                $this->logReplyFailure($business->id, $conversation, 'Gmail reply failed unexpectedly.', [
                    'error' => $exception->getMessage(),
                ]);

                return back()->with('error', 'Reply saved locally, but Gmail did not confirm delivery: '.$exception->getMessage());
            }
        }

        if ($conversation->channel === 'WhatsApp' && $message->body !== '') {
            try {
                $metaResponse = $metaConnectionService->sendWhatsAppText($conversation->fresh('connectedAccount'), $message->body);
                $deliveryMeta = [
                    'meta_response' => $metaResponse,
                    'whatsapp_message_id' => $metaResponse['messages'][0]['id'] ?? null,
                ];
            } catch (\Throwable $exception) {
                report($exception);
                $this->logReplyFailure($business->id, $conversation, 'Meta rejected the WhatsApp reply.', ['error' => $exception->getMessage()]);

                return back()->with('error', 'Reply saved locally, but Meta did not confirm WhatsApp delivery.');
            }
        }

        $metaAccount = $conversation->connectedAccount;
        $isRealMetaAccount = str_starts_with((string) ($metaAccount?->provider_meta['provider'] ?? ''), 'meta_');
        if (in_array($conversation->channel, ['Facebook', 'Instagram'], true) && $message->body !== '' && $isRealMetaAccount) {
            try {
                $metaResponse = $metaConnectionService->sendMessengerText($conversation->fresh('connectedAccount'), $message->body);
                $deliveryMeta = [
                    'meta_response' => $metaResponse,
                    'meta_message_id' => $metaResponse['message_id'] ?? null,
                    'meta_recipient_id' => $metaResponse['recipient_id'] ?? null,
                ];
            } catch (\Throwable $exception) {
                report($exception);
                $this->logReplyFailure($business->id, $conversation, 'Meta rejected the '.$conversation->channel.' reply.', ['error' => $exception->getMessage()]);

                return back()->with('error', 'Reply saved locally, but Meta did not confirm '.$conversation->channel.' delivery.');
            }
        }

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $conversation->connected_account_id,
            'event_type' => 'manual_reply_saved',
            'status' => 'success',
            'message' => 'A staff reply was saved and the conversation is waiting for the customer.',
            'metadata' => $deliveryMeta === [] ? null : $deliveryMeta,
        ]);

        return back()->with('status', 'Reply sent.');
    }

    public function pulse(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $selectedId = $request->integer('conversation');

        $selectedMessageCount = null;
        $selectedLatestMessageActivity = null;

        if ($selectedId) {
            $selectedConversationExists = Conversation::where('business_id', $business->id)
                ->where('id', $selectedId)
                ->exists();

            if ($selectedConversationExists) {
                $selectedMessageCount = Message::where('business_id', $business->id)
                    ->where('conversation_id', $selectedId)
                    ->count();
                $selectedLatestMessageActivity = Message::where('business_id', $business->id)
                    ->where('conversation_id', $selectedId)
                    ->max('updated_at') ?: '';
            }
        }

        return response()
            ->json([
                'version' => $this->inboxVersion($business->id),
                'conversation_id' => $selectedId ?: null,
                'selected_message_count' => $selectedMessageCount,
                'selected_latest_message_activity' => $selectedLatestMessageActivity,
                'server_time' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function takeOver(Request $request, Conversation $conversation)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($conversation->business_id === $business->id, 403);
        $conversation->update([
            'status' => Conversation::STATE_NEEDS_HUMAN,
            'ai_mode' => 'human',
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $conversation->connected_account_id,
            'event_type' => 'human_takeover',
            'status' => 'success',
            'message' => 'AI replies were disabled for this conversation.',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'conversation_status' => $conversation->status,
                'ai_mode' => $conversation->ai_mode,
            ]);
        }

        return back()->with('status', 'Human takeover enabled.');
    }

    public function resumeAi(Request $request, Conversation $conversation)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($conversation->business_id === $business->id, 403);
        $conversation->update([
            'status' => Conversation::STATE_AI_HANDLING,
            'ai_mode' => 'auto',
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $conversation->connected_account_id,
            'event_type' => 'ai_resumed',
            'status' => 'success',
            'message' => 'AI mode was resumed for this conversation without sending an automatic reply.',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'conversation_status' => $conversation->status,
                'ai_mode' => $conversation->ai_mode,
            ]);
        }

        return back()->with('status', 'AI resumed.');
    }

    public function close(Request $request, Conversation $conversation)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($conversation->business_id === $business->id, 403);

        $conversation->update([
            'status' => Conversation::STATE_CLOSED,
            'ai_mode' => 'off',
        ]);

        return back()->with('status', 'Conversation closed.');
    }

    private function unreadCount(Conversation $conversation, mixed $lastReadAt): int
    {
        $query = $conversation->messages()->where('direction', 'incoming');

        if (! $lastReadAt) {
            return (int) $query->count();
        }

        return (int) $query->where('created_at', '>', $lastReadAt)->count();
    }

    private function inboxVersion(int $businessId): string
    {
        return implode('|', [
            Conversation::where('business_id', $businessId)->max('last_message_at') ?: '',
            Message::where('business_id', $businessId)->max('updated_at') ?: '',
            Conversation::where('business_id', $businessId)->count(),
            Message::where('business_id', $businessId)->count(),
        ]);
    }

    private function logReplyFailure(int $businessId, Conversation $conversation, string $message, array $metadata = []): void
    {
        AutomationLog::create([
            'business_id' => $businessId,
            'connected_account_id' => $conversation->connected_account_id,
            'event_type' => 'manual_reply_failed',
            'status' => 'failed',
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
