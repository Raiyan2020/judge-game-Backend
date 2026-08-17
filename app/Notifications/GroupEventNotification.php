<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The single notification used by [App\Services\GroupEventService] for every
 * group event (case progression, role/permission/law changes). Carries a
 * localized title/body, the event `type` (shared vocabulary with the app's
 * notification switch), and the target ids so the app can deep-link.
 *
 * `broadcast` gives it a realtime path (the older case/call notifications lacked
 * one); FcmChannel pushes it once FIREBASE_CREDENTIALS is set.
 */
class GroupEventNotification extends Notification
{
    use Queueable;

    /**
     * @param array{type:string,title:array,body:array,group_id:?int,model_id:?int} $data
     */
    public function __construct(protected array $data = []) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->data['type'],
            'group_id' => $this->data['group_id'] ?? null,
            'model_id' => $this->data['model_id'] ?? null,
            'title' => [
                'ar' => $this->data['title']['ar'] ?? null,
                'en' => $this->data['title']['en'] ?? null,
            ],
            'body' => [
                'ar' => $this->data['body']['ar'] ?? null,
                'en' => $this->data['body']['en'] ?? null,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
