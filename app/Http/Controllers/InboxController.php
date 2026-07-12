<?php

namespace App\Http\Controllers;

use App\Models\AutomationLog;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Services\ConversationMessageService;
use App\Services\TelegramConnectionService;
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
        $allowedChannels = ['All', 'Instagram', 'WhatsApp', 'Facebook', 'Gmail', 'Telegram'];

        if (! in_array($activeChannel, $allowedChannels, true)) {
            $activeChannel = 'All';
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
            ->where('business_id', $business->id)
            ->latest('last_message_at');

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

        $conversations = $conversationQuery
            ->limit(self::CONVERSATION_PAGE_SIZE + 1)
            ->get();
        $hasMoreConversations = $conversations->count() > self::CONVERSATION_PAGE_SIZE;
        $conversations = $conversations->take(self::CONVERSATION_PAGE_SIZE)->values();

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
            'counts' => $counts,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'conversationIsOpen' => $conversationIsOpen,
            'search' => $search,
            'filterMeta' => InboxUi::stateFilters(),
            'channelMeta' => InboxUi::channels(),
            'hasMoreConversations' => $hasMoreConversations,
            'conversationPageSize' => self::CONVERSATION_PAGE_SIZE,
            'messageHistoryLimit' => self::MESSAGE_HISTORY_LIMIT,
            'aiSettings' => $aiSettings,
        ]);
    }

    public function reply(
        Request $request,
        Conversation $conversation,
        ConversationMessageService $conversationMessageService,
        TelegramConnectionService $telegramConnectionService
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
            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => ['file', 'max:12288'],
        ]);

        $attachments = $request->file('attachments', []);

        foreach ($attachments as $attachment) {
            if (str_starts_with((string) $attachment->getMimeType(), 'video/')) {
                return back()->with('error', 'Video uploads are not supported here. Use images, files, or audio notes.');
            }
        }

        $message = $conversationMessageService->saveOutgoing(
            $conversation,
            trim((string) ($validated['body'] ?? '')),
            'human',
            attachments: $attachments,
        );
        $deliveryMeta = [];

        if ($conversation->channel === 'Telegram' && trim((string) ($validated['body'] ?? '')) !== '') {
            try {
                $telegramResponse = $telegramConnectionService->sendTextMessage($conversation->fresh('connectedAccount'), $message->body);
                $deliveryMeta = [
                    'telegram_response' => $telegramResponse,
                    'telegram_message_id' => $telegramResponse['result']['message_id'] ?? null,
                    'telegram_chat_id' => $telegramResponse['result']['chat']['id'] ?? $conversation->customer_external_id,
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

    public function takeOver(Request $request, Conversation $conversation)
    {
        $business = $request->attributes->get('currentBusiness');
        abort_unless($conversation->business_id === $business->id, 403);
        $aiSettings = AiSetting::firstOrCreate(['business_id' => $business->id]);

        if (! $aiSettings->human_takeover_enabled) {
            return back()->with('status', 'Human takeover is disabled in AI settings.');
        }

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
        $aiSettings = AiSetting::firstOrCreate(['business_id' => $business->id]);

        if (! $aiSettings->human_takeover_enabled) {
            return back()->with('status', 'Human takeover is disabled in AI settings.');
        }

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
        $latestMessage = $conversation->latestMessage;

        if (! $latestMessage || $latestMessage->direction !== 'incoming') {
            return 0;
        }

        if (! $lastReadAt) {
            return 1;
        }

        return $latestMessage->created_at?->gt($lastReadAt) ? 1 : 0;
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
