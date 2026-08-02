<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleTitleSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this['user']['id'],
                'name' => $this['user']['name'],
                'points' => $this['user']['points'] ?? 0,
            ],
            'current_title' => $this['current_title'],
            'available_titles_count' => $this['available_titles_count'],
            'used_titles_count' => $this['used_titles_count'],
        ];
    }
}
