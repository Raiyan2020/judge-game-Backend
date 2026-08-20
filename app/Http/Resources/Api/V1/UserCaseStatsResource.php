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
            // `participated_cases`, not `participated_judges`: the app reads the
            // former (with the latter only as a legacy fallback), and this
            // default used to disagree with every producer of the value.
            // case_stats carries both the legacy 4 fields AND a role-specific
            // `tiles` list (JG-008/JG-017) — the app prefers tiles when present.
            'case_stats' => $this->case_stats ?? [
                'participated_cases' => 0,
                'appeal_judgments' => 0,
                'acquittal_judgments' => 0,
                'first_instance_judgments' => 0,
                'tiles' => [],
            ],
        ];
    }
}
