<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatPollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chat_message_id' => $this->chat_message_id,
            'type' => $this->type,
            'data' => $this->data,
            'options' => $this->relationLoaded('options') ? ChatPollOptionsResource::collection($this->options)
                : [],
        ];
    }
}
