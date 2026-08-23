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
        // FcmChannel pushes this to the invitee's device (it reads the ar/en
        // title+body from toArray). Without it the invite reached only the
        // in-app bell, never a push — the other notifications already push.
        return ['database', 'broadcast', \App\Notifications\Channels\FcmChannel::class];
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
                'ar' => 'دعوة للانضمام إلى ' . $groupName,
                'en' => 'Invitation to join ' . $groupName,
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
