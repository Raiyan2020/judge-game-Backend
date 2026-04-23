<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            'status' => $this->status,
            'is_final' => (bool) $this->is_final,
            'final_judgment' => $this->final_judgment,

            'group' => [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
            ],

            'plaintiff' => $this->whenLoaded('plaintiff', function () {
                return [
                    'id' => $this->plaintiff?->user?->id,
                    'name' => $this->plaintiff?->user?->name,
                    'image' => $this->plaintiff?->user?->image,
                ];
            }),

            'defendant' => $this->relationLoaded('defendant') ? [
                'id' => $this->defendant?->user?->id,
                'name' => $this->defendant?->user?->name,
                'image' => $this->defendant?->user?->image,
            ] : null,
           

            'witnesses' => $this->relationLoaded('witnesses') ? 
                $this->witnesses->map(function ($witness) {
                    return [
                        'id' => $witness?->user?->id,
                        'name' => $witness?->user?->name,
                        'image' => $witness?->user?->image,
                    ];
                })
            : [],

            'laws' => $this->relationLoaded('groupLaws') ? GroupLawResource::collection($this->groupLaws) : [], 
            

            'laws' => $this->relationLoaded('groupLaws') ? GroupLawResource::collection($this->groupLaws) : [],

            'opinions' => $this->relationLoaded('opinions') ? LegalCaseOpinionResource::collection($this->opinions) : [],

            'images' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('images')) :[],
            'videos' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('videos')) :[],
            'audios' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('audios')) :[],

            'created_at' => $this->created_at,
        ];
    }
}
