<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseNewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
           'group' => $this->relationLoaded('group') ? [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
           ] : null,
           'content' => $this->generateContent(),
           'created_at' => $this->created_at->diffForHumans(),  

        ];
    }

}