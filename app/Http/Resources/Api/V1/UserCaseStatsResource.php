<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserCaseStatsResource extends JsonResource
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
            'country_image' => $this->country?->image,
            'case_stats' => $this->case_stats ?? [
                'participated_judges' => 0,
                'appeal_judgments' => 0,
                'acquittal_judgments' => 0,
                'first_instance_judgments' => 0,
            ],

         
        ];
    }
}
