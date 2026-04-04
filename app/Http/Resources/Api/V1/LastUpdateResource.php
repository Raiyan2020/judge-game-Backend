<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LastUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $local = app()->getLocale();
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'version' => $this->version,
            'display_speed_in_seconds' => $this->display_speed,
            'created_at' => $this->created_at->locale($local)->translatedFormat('j-F-Y'),
        ];
    }
}