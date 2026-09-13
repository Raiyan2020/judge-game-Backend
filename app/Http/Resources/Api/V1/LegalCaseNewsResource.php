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
           // Rendered for THIS reader: the same row reads «رفعت قضية ضد فلان» to
           // the filer, «رُفعت قضية ضدك» to the defendant and a neutral third
           // person to everyone else. Without the viewer the sentence was
           // written from nobody's side and came out backwards for the filer
           // (M-04). Falls back to the sanctum guard for any route where the
           // default guard is not resolved.
           'content' => $this->generateContent($request->user() ?? auth('sanctum')->user()),
           // The ids behind that sentence, so the app can link the two people
           // (and render its own wording if it ever needs to).
           'actor_id' => $this->actor_id,
           'subject_id' => $this->subject_id,
           'created_at' => $this->created_at->diffForHumans(),

        ];
    }

}