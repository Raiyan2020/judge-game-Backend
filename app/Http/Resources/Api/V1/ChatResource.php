<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->otherUser->first();

        return [
            'id' => $this->id,

            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'image' => $user?->image,
                'is_online' => (bool) $user?->status === 'online',
            ],

            'last_message' => [
                'id' => $this->lastMessage?->id,
                'type' => $this->lastMessage?->type,
                'message' => $this->lastMessage?->message,
                'created_at' => $this->lastMessage?->created_at?->diffForHumans(),
            ],

            'unread_count' => $this->unread_count,
        ];
    }
}
