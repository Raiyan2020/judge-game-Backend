<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleTitleResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'requirements' => $this['requirements'],
            'completed' => $this['completed'],
            'used' => $this['used'],
            'used_at' => $this['used_at'],
        ];
    }
}