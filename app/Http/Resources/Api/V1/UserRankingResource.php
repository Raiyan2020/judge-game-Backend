<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserRankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'points' => $this->relationLoaded('points') ? [
                'total_points' => $this->points?->total_points ?? 0,
                'lawyer_points' => $this->points?->lawyer_points ?? 0,
                'judge_points' => $this->points?->judge_points ?? 0,
                'consultant_points' => $this->points?->consultant_points ?? 0,
                'citizen_points' => $this->points?->citizen_points ?? 0
            ] : null  ,
            'country_image' => $this->country?->image,
            'top_group'=> $this->top_group_name ?? null,
        ];
    }
}
