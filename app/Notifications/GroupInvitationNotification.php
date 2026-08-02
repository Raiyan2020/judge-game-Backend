<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when they are invited to a group. Carries THIS group's id and
 * name so the app's notification + invitation screen show the correct group
 * (previously no notification was sent at all and the screen used mock data).
 */
class GroupInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Group $group,
        protected ?User $inviter = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $groupName = $this->group->name;
        $inviter = $this->inviter?->name ?? '';

        return [
            'group_id' => $this->group->id,
            'image' => $this->group->image,
            'inviter_name' => $inviter,
            'title' => [
                'ar' => $groupName,
                'en' => $groupName,
            ],
            'body' => [
                'ar' => trim($inviter . ' دعاك للانضمام إلى ' . $groupName),
                'en' => trim($inviter . ' invited you to join ' . $groupName),
            ],
            'type' => 'group_invite',
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
