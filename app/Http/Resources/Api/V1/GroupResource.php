<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'creator_id' => $this->user_id,
            'description' => $this->description,
            'members_count' => $this->members_count ?? 1,
            // The signed-in user's membership status in this group
            // (accepted | pending) — lets the app tell a joined group from a
            // pending invitation.
            'status' => $this->pivot?->status ?? null,
            // Real group points (sum of members' points) + global rank, attached
            // by GroupService::getUserGroups. Absent on endpoints that don't set
            // them → 0 (the app renders "—" for an unranked/zero group).
            'points' => (int) ($this->points ?? 0),
            'global_rank' => (int) ($this->global_rank ?? 0),
            // The owner is always the judge — force it so a drifted pivot row
            // can't make the creator read as a citizen (which would wrongly let
            // them file cases and mislabel them in role screens).
            'my_role' => ((int) $this->user_id === (int) auth()->id())
                ? 'judge'
                : ($this->pivot?->role ?? null),
        ];
    }
}
