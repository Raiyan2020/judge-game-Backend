<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// NOT ShouldQueue: with QUEUE_CONNECTION=database and no worker running, a
// queued notification never dispatches at all — neither the database row nor
// the FCM push. Sending synchronously (the send is small and fail-soft in
// FcmChannel) makes message notifications actually arrive, matching the case /
// call / invite notifications which are already synchronous.
class NewMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', \App\Notifications\Channels\FcmChannel::class];
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
