<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->relationLoaded('chat') ? $this->chat?->group_id : null,
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            // Null user for a system message (user_id null) — guard the deref.
            'user' => $this->relationLoaded('user') && $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ] : null,
         //   'receiver' => $this->receiver ? new UserResource($this->whenLoaded('receiver')) : null,
            'message' => $this->message,
            'type' => $this->type,
            'attachment' => $this->attachment,
            'poll' => $this->relationLoaded('poll') ? new ChatPollResource($this->poll) : null,
            // 12-hour clock with am/pm — 'H' (24h) + 'a' rendered "20:23 pm".
            'created_at' => $this->created_at?->format('h:i a'),
        ];
    }
}
