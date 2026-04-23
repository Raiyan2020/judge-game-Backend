<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseOpinionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'legal_case_id' => $this->legal_case_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'opinion' => $this->opinion,
            'closing_statements' => $this->closing_statements,
            'is_final' => (bool) $this->is_final,
            'created_at' => $this->created_at,
            'images' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('images')) :[],
            'videos' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('videos')) :[],
            'audios' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('audios')) :[],

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'image' => $this->user?->image,
            ],
        ];
    }

}