<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast']; 
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_name' => $this->message->user->name,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'group_id' => $this->message->group_id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_name' => $this->message->user?->name,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'group_id' => $this->message->group_id,
        ];
    }
}
