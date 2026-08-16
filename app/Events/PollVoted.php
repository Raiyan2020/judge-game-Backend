<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A vote was cast on a poll — pushes the updated per-option tallies to the chat
 * channel so every member's bar moves live. Carries counts only (never a
 * per-user `is_mine`), so a receiver keeps their OWN standing selection and just
 * reconciles the totals.
 */
class PollVoted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $chatId;
    public int $messageId;
    public array $options;

    /**
     * @param  \App\Models\ChatPoll  $poll  freshly re-counted (options.votes)
     */
    public function __construct($poll)
    {
        $this->chatId = (int) ($poll->chatMessage?->chat_id ?? 0);
        $this->messageId = (int) $poll->chat_message_id;
        $this->options = $poll->options->map(fn ($o) => [
            'id' => (int) $o->id,
            'votes_count' => (int) ($o->votes_count ?? $o->votes()->count()),
        ])->values()->all();
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'poll.voted';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'options' => $this->options,
        ];
    }
}
