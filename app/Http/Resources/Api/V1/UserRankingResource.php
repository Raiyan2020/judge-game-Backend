<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserRankingResource extends JsonResource
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
            'points' => $this->relationLoaded('points') ? [
                'total_points' => $this->points?->total_points ?? 0,
                'lawyer_points' => $this->points?->lawyer_points ?? 0,
                'judge_points' => $this->points?->judge_points ?? 0,
                'consultant_points' => $this->points?->consultant_points ?? 0,
                'citizen_points' => $this->points?->citizen_points ?? 0
            ] : null  ,
            'country_image' => $this->country?->image ?? $this->fallbackCountryImage(),
            'top_group'=> $this->top_group_name ?? null,
        ];
    }

    /**
     * Flag URL for a user whose `country_id` is null (so the `country` relation
     * gives no flag and the app shows a globe). Fall back to matching a Country
     * by the user's dial `country_code` — the same nullable-`country_id` /
     * `country_code`-fallback situation `UserRepository` handles for local
     * ranks. The code→image map is memoised for the whole collection, so this
     * costs ONE query per response, not one per row.
     */
    protected function fallbackCountryImage(): ?string
    {
        if (empty($this->country_code)) {
            return null;
        }

        return static::countryImagesByCode()->get($this->country_code);
    }

    /**
     * Dial `country_code` => flag image URL, built once per request.
     */
    protected static function countryImagesByCode(): Collection
    {
        static $map = null;

        if ($map === null) {
            $map = Country::query()
                ->whereNotNull('country_code')
                ->get(['id', 'image', 'country_code'])
                ->mapWithKeys(fn ($country) => [
                    $country->country_code => $country->image,
                ]);
        }

        return $map;
    }
}
