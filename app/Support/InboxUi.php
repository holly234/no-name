<?php

namespace App\Support;

use App\Models\Conversation;

class InboxUi
{
    public static function stateFilters(): array
    {
        return [
            'All' => [
                'label' => 'All conversations',
                'icon' => '<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/>',
            ],
            Conversation::STATE_NEEDS_HUMAN => [
                'label' => 'Needs human',
                'icon' => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>',
            ],
            Conversation::STATE_AI_HANDLING => [
                'label' => 'AI handling',
                'icon' => '<path d="m12 3 1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3Z"/><path d="M5 15v4"/><path d="M19 15v4"/>',
            ],
            Conversation::STATE_INFORMATIONAL => [
                'label' => 'Info only',
                'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
            ],
            Conversation::STATE_WAITING => [
                'label' => 'Waiting',
                'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            ],
            Conversation::STATE_CLOSED => [
                'label' => 'Closed',
                'icon' => '<path d="M20 6 9 17l-5-5"/><path d="M21 12a9 9 0 1 1-3.28-6.95"/>',
            ],
        ];
    }

    public static function channels(): array
    {
        return [
            'All' => [
                'label' => 'All channels',
                'class' => 'bg-[#EEF0F3] text-[#111827]',
                'icon' => '<path fill="currentColor" d="M7 7a3 3 0 1 1 5.2 2.05A3.75 3.75 0 0 0 7 12.5V14H4v-1.5A3.75 3.75 0 0 1 7.8 8.75 3 3 0 0 1 7 7Zm10 0a3 3 0 1 0-5.2 2.05A3.75 3.75 0 0 1 17 12.5V14h3v-1.5a3.75 3.75 0 0 0-3.8-3.75A3 3 0 0 0 17 7ZM12 10a3.5 3.5 0 0 0-3.5 3.5V16h7v-2.5A3.5 3.5 0 0 0 12 10Zm0-1.2a2.9 2.9 0 1 0 0-5.8 2.9 2.9 0 0 0 0 5.8Z"/>',
            ],
            'Instagram' => [
                'label' => 'Instagram',
                'class' => 'bg-[#dd2a7b] text-white',
                'icon' => self::instagramIcon(),
            ],
            'WhatsApp' => [
                'label' => 'WhatsApp',
                'class' => 'bg-[#10B981] text-white',
                'icon' => self::whatsAppIcon(),
            ],
            'Facebook' => [
                'label' => 'Facebook',
                'class' => 'bg-[#1877f2] text-white',
                'icon' => self::facebookIcon(),
            ],
            'Gmail' => [
                'label' => 'Gmail',
                'class' => 'bg-[#ea4335] text-white',
                'icon' => self::gmailIcon(),
            ],
            'Telegram' => [
                'label' => 'Telegram',
                'class' => 'bg-[#229ED9] text-white',
                'icon' => self::telegramIcon(),
            ],
        ];
    }

    public static function statusMeta(string $status): array
    {
        return match ($status) {
            Conversation::STATE_NEEDS_HUMAN => [
                'label' => 'Needs staff attention',
                'class' => 'bg-pink-50 text-[#BE185D]',
                'icon' => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>',
            ],
            Conversation::STATE_WAITING => [
                'label' => 'Waiting for customer response',
                'class' => 'bg-[#FFFBEB] text-[#B45309]',
                'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            ],
            Conversation::STATE_AI_HANDLING => [
                'label' => 'Smart routing active',
                'class' => 'bg-[#ECFDF5] text-[#047857]',
                'icon' => '<path d="m12 3 1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3Z"/><path d="M5 15v4"/><path d="M19 15v4"/>',
            ],
            Conversation::STATE_INFORMATIONAL => [
                'label' => 'Informational email — no action needed',
                'class' => 'bg-[#EFF6FF] text-[#2563EB]',
                'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
            ],
            default => [
                'label' => 'Closed',
                'class' => 'bg-[#EEF0F3] text-[#6B7280]',
                'icon' => '<path d="M20 6 9 17l-5-5"/><path d="M21 12a9 9 0 1 1-3.28-6.95"/>',
            ],
        };
    }

    public static function channelMeta(string $channel): array
    {
        $channel = $channel === 'gmail' ? 'Gmail' : $channel;

        return self::channels()[$channel] ?? self::channels()['Instagram'];
    }

    public static function intentFor(Conversation $conversation): string
    {
        if ($conversation->channel === 'Gmail') {
            return $conversation->status === Conversation::STATE_INFORMATIONAL
                ? 'Automated email'
                : 'Email enquiry';
        }

        return match ($conversation->status) {
            Conversation::STATE_NEEDS_HUMAN => str_contains(strtolower($conversation->latestMessage?->body ?? ''), 'complaint') ? 'Complaint' : 'Discount',
            Conversation::STATE_WAITING => 'Booking',
            Conversation::STATE_AI_HANDLING => 'General',
            default => 'Resolved',
        };
    }

    private static function instagramIcon(): string
    {
        return '<path fill="currentColor" d="M12 7.4A4.6 4.6 0 1 0 12 16.6 4.6 4.6 0 0 0 12 7.4Zm0 1.65a2.95 2.95 0 1 1 0 5.9 2.95 2.95 0 0 1 0-5.9Zm5.04-.46a1.08 1.08 0 1 0 0-2.16 1.08 1.08 0 0 0 0 2.16Z"/><path fill="currentColor" fill-rule="evenodd" d="M7.5 2.75h9A4.75 4.75 0 0 1 21.25 7.5v9a4.75 4.75 0 0 1-4.75 4.75h-9A4.75 4.75 0 0 1 2.75 16.5v-9A4.75 4.75 0 0 1 7.5 2.75Zm0 1.7A3.05 3.05 0 0 0 4.45 7.5v9a3.05 3.05 0 0 0 3.05 3.05h9a3.05 3.05 0 0 0 3.05-3.05v-9a3.05 3.05 0 0 0-3.05-3.05h-9Z" clip-rule="evenodd"/>';
    }

    private static function whatsAppIcon(): string
    {
        return '<path fill="currentColor" d="M12.04 3.5a8.35 8.35 0 0 0-7.15 12.65L3.9 20.5l4.45-1.16A8.34 8.34 0 1 0 12.04 3.5Zm0 1.55a6.8 6.8 0 1 1-3.23 12.78l-.27-.15-2.52.66.67-2.44-.17-.28a6.8 6.8 0 0 1 5.52-10.57Zm-2.53 3.7c-.16-.36-.33-.37-.48-.38h-.41c-.14 0-.37.05-.56.26-.19.21-.74.72-.74 1.76s.76 2.05.86 2.19c.11.14 1.47 2.35 3.63 3.2 1.8.71 2.17.57 2.56.54.39-.04 1.27-.52 1.45-1.02.18-.5.18-.93.13-1.02-.06-.09-.2-.14-.42-.25l-1.48-.73c-.22-.11-.38-.16-.54.11-.16.27-.62.73-.76.88-.14.14-.28.16-.5.05-.23-.11-.95-.35-1.81-1.12-.67-.6-1.12-1.33-1.25-1.56-.13-.23-.01-.35.1-.46.1-.1.22-.27.33-.41.11-.14.15-.24.22-.4.07-.16.04-.3-.02-.41l-.67-1.62Z"/>';
    }

    private static function facebookIcon(): string
    {
        return '<path fill="currentColor" d="M14.25 8.2h1.85V5.1c-.32-.04-1.42-.13-2.7-.13-2.67 0-4.5 1.63-4.5 4.63v2.6H5.9v3.47h3v7.88h3.67v-7.88h3.02l.48-3.47h-3.5V9.95c0-1 .28-1.75 1.68-1.75Z"/>';
    }

    private static function gmailIcon(): string
    {
        return '<path fill="currentColor" d="M3.5 6.75A2.25 2.25 0 0 1 5.75 4.5h12.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H5.75a2.25 2.25 0 0 1-2.25-2.25V6.75Zm2.25-.65a.65.65 0 0 0-.65.65v.38L12 12.05l6.9-4.92v-.38a.65.65 0 0 0-.65-.65H5.75Zm13.15 3.03-6.44 4.6a.8.8 0 0 1-.92 0L5.1 9.13v8.12c0 .36.29.65.65.65h12.5c.36 0 .65-.29.65-.65V9.13Z"/>';
    }

    private static function telegramIcon(): string
    {
        return '<path fill="currentColor" d="M21.6 4.2c.25-1.08-.8-1.98-1.8-1.55L2.9 9.88c-1.15.49-1.1 2.12.08 2.53l4.25 1.47 1.64 5.18c.36 1.12 1.8 1.42 2.58.53l2.36-2.7 4.32 3.18c.92.68 2.24.16 2.5-.95L21.6 4.2ZM8 12.82l9.92-6.1c.18-.11.37.13.22.28l-7.98 7.87-.32 3.18-1.11-3.52L8 12.82Zm2.98 3.24 1.14 1.02-.92 1.05-.22-2.07Z"/>';
    }
}
