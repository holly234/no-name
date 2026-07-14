<?php

namespace App\Http\Controllers;

use App\Models\AutomationLog;
use App\Models\Conversation;
use App\Services\ConversationMessageService;
use App\Services\MessageIngestionService;
use Illuminate\Http\Request;

class N8nController extends Controller
{
    private function hasValidSecret(Request $request): bool
    {
        $expected = config('services.n8n.webhook_secret');

        return is_string($expected)
            && $expected !== ''
            && hash_equals($expected, (string) $request->header('X-N8N-SECRET'));
    }

    public function incomingMessage(Request $request, MessageIngestionService $messageIngestionService)
    {
        abort_unless($this->hasValidSecret($request), 401);

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'channel' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_external_id' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'confidence' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $conversation = $messageIngestionService->ingest($validated + ['source' => 'n8n']);

        return response()->json(['message' => 'n8n message processed.', 'conversation_id' => $conversation->id, 'state' => $conversation->status]);
    }

    public function saveOutgoingMessage(Request $request, ConversationMessageService $conversationMessageService)
    {
        abort_unless($this->hasValidSecret($request), 401);

        $validated = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'body' => ['required', 'string', 'max:4000'],
            'sender_type' => ['nullable', 'string', 'max:40'],
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);
        $message = $conversationMessageService->saveOutgoing(
            $conversation,
            $validated['body'],
            $validated['sender_type'] ?? 'system'
        );

        return response()->json(['message' => 'Outgoing message saved.', 'id' => $message->id]);
    }

    public function logEvent(Request $request)
    {
        abort_unless($this->hasValidSecret($request), 401);

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'event_type' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:1000'],
            'error_details' => ['nullable', 'string', 'max:4000'],
        ]);

        $log = AutomationLog::create($validated);

        return response()->json(['message' => 'Event logged.', 'id' => $log->id]);
    }
}
