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
            'group_id' => $this->group_id,
            'user_id' => $this->user_id,
            'user' => $this->relationLoaded('user') ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ] : null,
         //   'receiver' => $this->receiver ? new UserResource($this->whenLoaded('receiver')) : null,
            'message' => $this->message,
            'type' => $this->type,
            'file' => $this->file,
            'created_at' => $this->created_at?->format('H:i a'),
        ];
    }
}
