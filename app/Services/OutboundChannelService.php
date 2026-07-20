<?php

namespace App\Services;

use App\Models\Conversation;

class OutboundChannelService
{
    public function __construct(
        private readonly TelegramConnectionService $telegram,
        private readonly MetaConnectionService $meta,
    ) {}

    public function sendText(Conversation $conversation, string $body): array
    {
        $conversation->loadMissing('connectedAccount');

        if ($conversation->channel === 'Telegram') {
            $response = $this->telegram->sendTextMessage($conversation, $body);

            return ['telegram_response' => $response];
        }

        if ($conversation->channel === 'WhatsApp') {
            return ['meta_response' => $this->meta->sendWhatsAppText($conversation, $body)];
        }

        $isRealMetaAccount = str_starts_with((string) ($conversation->connectedAccount?->provider_meta['provider'] ?? ''), 'meta_');

        if (in_array($conversation->channel, ['Facebook', 'Instagram'], true) && $isRealMetaAccount) {
            return ['meta_response' => $this->meta->sendMessengerText($conversation, $body)];
        }

        return [];
    }
}
