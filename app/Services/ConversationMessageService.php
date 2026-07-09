<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ConversationMessageService
{
    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function saveOutgoing(Conversation $conversation, string $body, string $senderType = 'system', array $metadata = [], array $attachments = []): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'business_id' => $conversation->business_id,
            'direction' => 'outgoing',
            'sender_type' => $senderType,
            'body' => $body,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $conversation->update([
            'status' => Conversation::STATE_WAITING,
            'ai_mode' => $senderType === 'human' ? 'human' : $conversation->ai_mode,
            'last_message_at' => now(),
        ]);

        foreach ($attachments as $attachment) {
            $path = $attachment->store('manual-attachments/'.$conversation->business_id.'/'.$message->id);

            $message->attachments()->create([
                'business_id' => $conversation->business_id,
                'provider' => 'manual',
                'provider_attachment_id' => (string) Str::uuid(),
                'filename' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
                'disk' => 'local',
                'storage_path' => $path,
                'metadata' => [
                    'uploaded_by' => 'staff',
                    'original_extension' => $attachment->getClientOriginalExtension(),
                ],
            ]);
        }

        return $message;
    }

    public function markReadForUser(Conversation $conversation, int $userId): void
    {
        $conversation->reads()->updateOrCreate(
            ['user_id' => $userId],
            ['last_read_at' => now()]
        );
    }
}
