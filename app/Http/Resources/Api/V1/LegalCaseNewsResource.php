<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseNewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
           // The event kind (e.g. member_joined, case_closed, hearing_scheduled)
           // so the app can render a per-row icon, and the case id so a
           // case-scoped row is a tap target (null for group-only events).
           'type' => $this->type,
           'legal_case_id' => $this->legal_case_id,
           'group' => $this->relationLoaded('group') ? [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
           ] : null,
           'content' => $this->generateContent(),
           'created_at' => $this->created_at->diffForHumans(),

        ];
    }

}