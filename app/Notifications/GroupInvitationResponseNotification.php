<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the group OWNER when an invitee accepts or rejects their invitation,
 * so the inviter learns the outcome (previously accept/reject sent nothing).
 * The pivot has no per-invite sender column, so the owner is the target.
 */
class GroupInvitationResponseNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Group $group,
        protected User $responder,
        protected bool $accepted,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $groupName = $this->group->name;
        $who = $this->responder->name ?? '';
        $accepted = $this->accepted;

        return [
            'group_id' => $this->group->id,
            'image' => $this->group->image,
            'responder_name' => $who,
            'accepted' => $accepted,
            'title' => [
                'ar' => $groupName,
                'en' => $groupName,
            ],
            'body' => [
                'ar' => $accepted
                    ? trim($who . ' قبل دعوتك للانضمام إلى ' . $groupName)
                    : trim($who . ' رفض دعوتك للانضمام إلى ' . $groupName),
                'en' => $accepted
                    ? trim($who . ' accepted your invitation to ' . $groupName)
                    : trim($who . ' declined your invitation to ' . $groupName),
            ],
            'type' => $accepted ? 'group_invite_accepted' : 'group_invite_rejected',
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
