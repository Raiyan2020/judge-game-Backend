<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseJudgmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'legal_case_id' => $this->legal_case_id,
            'judgment_type' => $this->judgment_type,
            'stage' => $this->stage,
            'judgment_text' => $this->judgment_text,
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }
}
