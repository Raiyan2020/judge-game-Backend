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
            // The option the current user voted for (null when they haven't) so
            // the app's checkmark survives a reload.
            'my_vote_option_id' => $this->relationLoaded('options')
                ? optional($this->options->first(fn ($o) => ($o->mine_count ?? 0) > 0))->id
                : null,
            // The poll is open only while not closed AND not past its expiry —
            // the app locks voting when this is false.
            'can_vote' => !$this->is_closed
                && (is_null($this->expires_at) || $this->expires_at->isFuture()),
            'options' => $this->relationLoaded('options') ? ChatPollOptionsResource::collection($this->options)
                : [],
        ];
    }
}
