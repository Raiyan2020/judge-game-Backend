<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
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
            'token' => $this->token,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'username' => $this->username,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'full_phone' => $this->full_phone,
            'image' => $this->image,
            'gender' => $this->gender,
            'language' => $this->language,
            'notified' => $this->notified,
            'birthdate' => $this->birthdate,
            'status' => $this->status,
            'status_text' => __($this->status),
            'member_since' => $this->created_at->locale(app()->getLocale())->translatedFormat('j-F-Y'),
            'current_subscription' => new PackageSubscriptionResource($this->whenLoaded('activeSubscription')),
            'points' => $this->relationLoaded('points') ? $this->points?->total_points ?? 0 : null  ,
            'global_rank' => $this->relationLoaded('points') ? $this->globalRank() : null,
            'local_rank' => $this->relationLoaded('points') ? $this->localRank() : null,
            'plaintiff_cases_count' => $this->plaintiff_cases_count ,
            'defendant_cases_count' => $this->defendant_cases_count ,
            'plaintiff_wins_count' => $this->plaintiff_wins_count ,
            'plaintiff_losses_count' => $this->plaintiff_losses_count ,
            'defendant_wins_count' => $this->defendant_wins_count,
            'defendant_losses_count' => $this->defendant_losses_count

        ];
    }
}
