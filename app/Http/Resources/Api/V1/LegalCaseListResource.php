<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
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

            'plaintiff_lawyer' => $this->relationLoaded('plaintiffLawyer') ? [
                'id' => $this->plaintiffLawyer?->user?->id,
                'name' => $this->plaintiffLawyer?->user?->name,
                'image' => $this->plaintiffLawyer?->user?->image,
            ] : null,
            'defendant_lawyer' => $this->relationLoaded('defendantLawyer') ? [
                'id' => $this->defendantLawyer?->user?->id,
                'name' => $this->defendantLawyer?->user?->name,
                'image' => $this->defendantLawyer?->user?->image,
            ] : null,
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }
}
