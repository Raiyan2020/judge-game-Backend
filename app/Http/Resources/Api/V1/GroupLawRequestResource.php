<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupLawRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'group_law_id' => $this->group_law_id,
            'action' => $this->action,
            'description' => $this->description,
            'reason' => $this->reason,
            'status' => $this->status,
            'user' => new UserResource($this->whenLoaded('user') ?: $this->user),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
