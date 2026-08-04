<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One pending group invitation for the "My Invitations" screen. `id` is the
 * GROUP id (used to accept/reject via /groups/{id}/accept|reject). The inviter
 * is shown as the group owner (the pivot doesn't store who sent each invite).
 */
class GroupInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_name' => $this->name,
            'group_image' => $this->image,
            'inviter_name' => $this->owner?->name ?? '',
            'role' => $this->pivot?->role,
            'members_count' => $this->members_count ?? 0,
            // The invitation "About" card renders these; omitting them made the
            // description body always blank and the cases chip always "0".
            'description' => $this->description ?? '',
            'cases_count' => $this->legal_cases_count ?? 0,
        ];
    }
}
