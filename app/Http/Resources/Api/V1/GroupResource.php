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
            'my_role' => $this->pivot?->role ?? null,
        ];
    }
}
