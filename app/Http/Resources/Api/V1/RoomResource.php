<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authUser = null;

        if ($this->relationLoaded('users') && auth()->check()) {
            $authUser = $this->users->firstWhere('id', auth()->id());
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_text' => __($this->type),
            'admin_id' => $this->user_id,
            'is_joined' => (bool) $authUser,

            'is_muted' => $authUser
                ? (bool) $authUser->pivot->is_muted
                : false,

            'time_duration' => $this->created_at->diff(now())->format('%H:%I:%S'),
            'group' => $this->relationLoaded('group') ? [
                'id' => $this->group->id,
                'name' => $this->group->name
            ] : null,
            'user_count' => $this->users_count,
            'users' => $this->relationLoaded('users') ? $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_admin' => (bool) $user->pivot->is_admin,
                    'is_muted' => (bool) $user->pivot->is_muted
                ];
            })  : []
        ];
    }
}
