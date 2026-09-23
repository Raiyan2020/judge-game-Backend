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
            'sender_name' => $this->message->user?->name,
            // Read the real DB column. There is no `content` column on
            // chat_messages (the body lives in `message`), so the old read
            // returned null and the bell/push body was blank. Key stays
            // `content` because FcmChannel::titleBody uses it as the body
            // fallback.
            'content' => $this->message->message,
            // The client's push router keys on 'message'/'chat'. The raw
            // ChatMessage `type` is 'text'/'private' and routed nowhere.
            'type' => 'message',
            // Deep-link target: the CHAT id, so tapping opens
            // privateChatDetail(id). FcmChannel builds `related_data` from
            // group_id ?? model_id ?? id, so exposing the chat id as model_id
            // (and no group_id) makes the push land on the private chat.
            'model_id' => $this->message->chat_id,
            // The counterpart's user id (the message SENDER, from the reader's
            // side). The in-app notifications list routes the private chat by the
            // peer's user id (`receiver_id` path param) with the chat id as the
            // `chat_id` query, so a reply from that entry point posts to the right
            // person. `model_id` (chat id) alone can't identify the receiver.
            'sender_id' => $this->message->user_id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
