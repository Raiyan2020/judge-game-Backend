<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LegalCaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LegalCase $legalCase,
        protected array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'model_id' => $this->data['model_id'],
            'title' => [
                'ar' =>$this->data['title']['ar'] ,
                'en' => $this->data['title']['en'],
            ],
            'body' => [
                'ar' => $this->data['body']['ar'] ,
                'en' => $this->data['body']['en'] ,
            ],
            'type' => $this->data['type'],
        ];
    }
}