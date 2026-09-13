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
            // create_law | update_law | delete_law | ads — the app captions the
            // card with the operation (JG-019).
            'type' => $this->type,
            'data' => $this->data,
            // The law text BEFORE an edit/delete, so the card can show
            // before → after (data.description is the "after"). Null for a
            // create/ads poll (JG-018/JG-019).
            //
            // M-05: prefer the IMMUTABLE snapshot taken at proposal time
            // (`data.current_law`). The live `groupLaw` relation is wrong for a
            // SETTLED poll — an approved update has already rewritten that law,
            // and an approved delete removed it and nulled `group_law_id` — which
            // is why closed cards lost the targeted law / showed the post-edit
            // text as the "before". The live read stays only as the fallback for
            // polls created before the snapshot existed.
            'current_law' => ($this->data['current_law'] ?? null)
                ?: ($this->group_law_id ? optional($this->groupLaw)->description : null),
            // The option the current user voted for (null when they haven't) so
            // the app's checkmark survives a reload.
            'my_vote_option_id' => $this->relationLoaded('options')
                ? optional($this->options->first(fn ($o) => ($o->mine_count ?? 0) > 0))->id
                : null,
            // When the 24h vote window ends — ISO8601, null-safe — so the app can
            // render a live countdown on the poll card (M3b).
            'expires_at' => $this->expires_at?->toIso8601String(),
            // The poll is open only while not closed AND not past its expiry —
            // the app locks voting when this is false.
            'can_vote' => !$this->is_closed
                && (is_null($this->expires_at) || $this->expires_at->isFuture()),
            // Whether the 24h window has been settled (closed by vote or expiry).
            'is_closed' => (bool) $this->is_closed,
            // The settled outcome of a LAW poll: approved | rejected | null (no
            // law outcome — still open, or an ads poll which carries no verdict).
            'result' => $this->result,
            'options' => $this->relationLoaded('options') ? ChatPollOptionsResource::collection($this->options)
                : [],
        ];
    }
}
