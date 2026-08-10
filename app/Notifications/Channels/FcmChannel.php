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

        [$title, $body] = $this->titleBody($payload, $notifiable->language ?? null);
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
            // The app reads the deep-link target from `related_data`
            // (fcm_notification.dart), so sending only `id` left every push
            // tapping through to a generic list instead of the actual group,
            // case or chat. Both keys are sent so either reader works.
            $data['related_data'] = (string) $id;
        }

        $this->fcm->sendToToken($token, $title, $body, $data);
    }

    /**
     * Pull a display title/body out of a notification payload, tolerating both
     * the localized `['title'=>['ar'=>..], 'body'=>['ar'=>..]]` shape and the
     * flat `['sender_name'=>.., 'content'=>..]` message shape.
     */
    private function titleBody(array $p, ?string $locale = null): array
    {
        // Pick the RECIPIENT's language. This used to read `['ar']`
        // unconditionally, so an English user's push arrived in Arabic even
        // though every notification carries both variants.
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
        $fallback = $locale === 'ar' ? 'en' : 'ar';

        $title = $p['title'][$locale] ?? $p['title'][$fallback] ?? $p['title'] ?? $p['sender_name'] ?? '';
        $body = $p['body'][$locale] ?? $p['body'][$fallback] ?? $p['body'] ?? $p['content'] ?? '';

        return [(string) $title, (string) $body];
    }
}
