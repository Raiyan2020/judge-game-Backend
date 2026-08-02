<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatPollOptionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option' => $this->option,
            'votes_count' =>  $this->votes_count ,
            // Whether the CURRENT user's vote sits on this option (from the
            // per-user vote count eager-loaded in MessageService::index). Lets
            // the app restore the selection on reload.
            'is_mine' => ($this->mine_count ?? 0) > 0,
        ];
    }
}
