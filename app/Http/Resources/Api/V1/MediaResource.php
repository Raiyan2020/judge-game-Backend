<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'url' => $this->getUrl(),
            'name' => $this->file_name,
            'size' => $this->size,
            'type' => $this->mime_type,
        ];
    }

}