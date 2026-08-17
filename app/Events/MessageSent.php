<?php
namespace App\Events;
use App\Http\Resources\Api\V1\MessageResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        // poll.options too, so a broadcast poll/announcement carries its options
        // (MessageResource only serializes `poll` when the relation is loaded).
        // Harmless for a plain text message — the relation just resolves null.
        $this->message = $message->load('user', 'chat', 'poll.options');
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
    
    public function broadcastWith()
    {
        return (new MessageResource($this->message))->resolve();
    }
}