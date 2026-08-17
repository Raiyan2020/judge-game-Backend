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
        // A phone number is PII and is never needed to RENDER another user:
        // the app only ever sends a phone (to invite someone), it never reads
        // one off a member. Exposing it here meant any member list — and those
        // were readable by any signed-in user — doubled as a phone directory.
        // "Carries a token" counts as self: `POST /auth/verify-code` is
        // UNAUTHENTICATED (the token is minted inside that very request), so
        // `auth('sanctum')->id()` is null there — and `when(false)` REMOVES the
        // key rather than nulling it, which would have stripped the phone out
        // of the one response the app builds its session from.
        $isSelf = $this->token !== null || auth('sanctum')->id() === $this->id;

        return [
            'id' => $this->id,
            'token' => $this->token,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'username' => $this->username,
            'phone' => $this->when($isSelf, fn () => $this->phone),
            'country_code' => $this->when($isSelf, fn () => $this->country_code),
            'full_phone' => $this->when($isSelf, fn () => $this->full_phone),
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
            'defendant_losses_count' => $this->defendant_losses_count,
            // Present only where the caller resolved it (group members list) —
            // the submit-case form reads it to red-flag an immune defendant.
            'has_immunity' => $this->when(
                ! is_null($this->has_immunity),
                fn () => (bool) $this->has_immunity
            ),

        ];
    }
}
