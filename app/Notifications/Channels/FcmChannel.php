<?php

namespace App\Notifications\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

/**
 * Custom notification channel that pushes via FCM (HTTP v1). Add `FcmChannel::class`
 * to a notification's `via()` and it will send to the notifiable's `fcm_token`
 * using either the notification's `toFcm($notifiable)` payload or, failing that,
 * its `toArray($notifiable)` (reading the ar `title`/`body` + `type`).
 *
 * Before this, only group invites pushed (an explicit FcmService call in the
 * service). Message / case / call notifications reached only the database.
 * Still a no-op until FIREBASE_CREDENTIALS is configured.
 */
class FcmChannel
{
    public function __construct(protected FcmService $fcm) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $token = $notifiable->fcm_token ?? null;
        if (empty($token)) {
            return;
        }

        $payload = method_exists($notification, 'toFcm')
            ? $notification->toFcm($notifiable)
            : $notification->toArray($notifiable);

        [$title, $body] = $this->titleBody($payload);
        if ($title === '' && $body === '') {
            return;
        }

        $data = [];
        if (! empty($payload['type'])) {
            $data['type'] = (string) $payload['type'];
        }
        $id = $payload['group_id'] ?? $payload['model_id'] ?? $payload['id'] ?? null;
        if ($id !== null) {
            $data['id'] = (string) $id;
        }

        $this->fcm->sendToToken($token, $title, $body, $data);
    }

    /**
     * Pull a display title/body out of a notification payload, tolerating both
     * the localized `['title'=>['ar'=>..], 'body'=>['ar'=>..]]` shape and the
     * flat `['sender_name'=>.., 'content'=>..]` message shape.
     */
    private function titleBody(array $p): array
    {
        $title = $p['title']['ar'] ?? $p['title'] ?? $p['sender_name'] ?? '';
        $body = $p['body']['ar'] ?? $p['body'] ?? $p['content'] ?? '';
        return [(string) $title, (string) $body];
    }
}
