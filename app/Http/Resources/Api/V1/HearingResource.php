<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class HearingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'legal_case_id' => $this->legal_case_id,
            'room_id' => $this->room_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status,
        ];
    }
}
