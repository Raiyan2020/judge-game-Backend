<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the "best groups" leaderboard (JG-010): the group's identity, its
 * summed member points, its rank, and how many members it has.
 */
class BestGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'points' => (int) ($this->points ?? 0),
            'rank' => (int) ($this->global_rank ?? 0),
            'members_count' => (int) ($this->users_count ?? 0),
        ];
    }
}
