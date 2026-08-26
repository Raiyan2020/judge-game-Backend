<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One pending group invitation for the "My Invitations" screen. `id` is the
 * GROUP id (used to accept/reject via /groups/{id}/accept|reject). The inviter
 * is the pivot's `invited_by` (who actually sent this invite), falling back to
 * the group owner for invites that predate inviter tracking (N5).
 */
class GroupInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inviterId = $this->pivot?->invited_by;
        $inviter = $inviterId ? \App\Models\User::find($inviterId) : null;

        return [
            'id' => $this->id,
            'group_name' => $this->name,
            'group_image' => $this->image,
            'inviter_id' => $inviter?->id ?? $this->owner?->id,
            'inviter_name' => $inviter?->name ?? $this->owner?->name ?? '',
            'role' => $this->pivot?->role,
            'members_count' => $this->members_count ?? 0,
            // The invitation "About" card renders these; omitting them made the
            // description body always blank and the cases chip always "0".
            'description' => $this->description ?? '',
            'cases_count' => $this->legal_cases_count ?? 0,
        ];
    }
}
