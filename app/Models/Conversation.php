<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public const STATE_AI_HANDLING = 'AI Handling';

    public const STATE_WAITING = 'Waiting';

    public const STATE_NEEDS_HUMAN = 'Needs Human';

    public const STATE_CLOSED = 'Closed';

    public const STATES = [
        self::STATE_AI_HANDLING,
        self::STATE_WAITING,
        self::STATE_NEEDS_HUMAN,
        self::STATE_CLOSED,
    ];

    protected $fillable = [
        'business_id',
        'connected_account_id',
        'customer_id',
        'customer_name',
        'customer_external_id',
        'channel',
        'status',
        'ai_mode',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function connectedAccount()
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function reads()
    {
        return $this->hasMany(ConversationRead::class);
    }

    public function readForUser(User $user)
    {
        return $this->hasOne(ConversationRead::class)->where('user_id', $user->id);
    }

    public function isHumanControlled(): bool
    {
        return $this->ai_mode === 'human';
    }

    public function replyDisabled(): bool
    {
        if ($this->relationLoaded('messages')) {
            return $this->messages->contains(fn (Message $message) => $this->messageDisablesReplies($message));
        }

        return $this->replyDisabledMessagesQuery()->exists();
    }

    public function replyDisabledReason(): ?string
    {
        $message = $this->relationLoaded('messages')
            ? $this->messages->first(fn (Message $message) => $this->messageDisablesReplies($message))
            : $this->replyDisabledMessagesQuery()->first();

        return $message?->metadata['reply_disabled_reason'] ?? ($message ? 'Automated sender' : null);
    }

    private function replyDisabledMessagesQuery()
    {
        return $this->messages()
            ->where(function ($query) {
                $query
                    ->where('metadata->reply_disabled', true)
                    ->orWhere('metadata->from_email', 'like', '%noreply%')
                    ->orWhere('metadata->from_email', 'like', '%no-reply%')
                    ->orWhere('metadata->from_email', 'like', '%donotreply%')
                    ->orWhere('metadata->from_email', 'like', '%do-not-reply%');
            });
    }

    public function messageDisablesReplies(Message $message): bool
    {
        if ((bool) ($message->metadata['reply_disabled'] ?? false)) {
            return true;
        }

        if (($message->metadata['source'] ?? null) !== 'gmail' && $this->channel !== 'Gmail') {
            return false;
        }

        $fromEmail = strtolower((string) ($message->metadata['from_email'] ?? $this->customer_external_id));

        return (bool) preg_match('/(^|[._+-])(no-?reply|do-?not-?reply|donotreply)([._+-]|@)/i', $fromEmail);
    }
}
