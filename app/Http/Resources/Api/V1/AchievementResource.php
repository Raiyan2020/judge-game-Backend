<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One achievement rung in the exact shape the Flutter app's `AchievementModel`
 * expects: strict types (`id` string, `tier`/`points`/`current`/`target` int,
 * `is_completed` bool), `requirements` mapped to `tasks`, and `used_at` surfaced
 * as `completed_date` (may be null). Fed the array `RoleAchievementService`
 * builds.
 */
class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this['id'],
            'tier' => (int) ($this['tier'] ?? 1),
            'title' => (string) $this['title'],
            'points' => (int) ($this['reward_points'] ?? 0),
            'is_completed' => (bool) $this['completed'],
            'completed_date' => $this['used_at'],
            'tasks' => collect($this['requirements'])->map(fn ($r) => [
                'description' => (string) $r['title'],
                'current' => (int) $r['current'],
                'target' => (int) $r['required'],
            ])->values(),
        ];
    }
}
