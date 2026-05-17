<?php

namespace App\Notifications;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCallNotification extends Notification
{
    use Queueable;

    public function __construct(protected array $data = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        

        return [
            'model_id' => $this->data['model_id'] ,
            'title' => [
                'ar' => $this->data['title']['ar'] ?? null,
                'en' => $this->data['title']['en'] ?? null,
            ],
            'body' => [
                'ar' => $this->data['body']['ar'] ?? null,
                'en' => $this->data['body']['en'] ?? null,
            ],
            'type' => $this->data['type'] ?? 'call',
        ];
    }
}
