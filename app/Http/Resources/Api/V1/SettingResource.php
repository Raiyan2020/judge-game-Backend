<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'value' => $this->value,
            'updated_at' => $this->updated_at->locale($local)->translatedFormat('j-F-Y'),
        ];
    }
}