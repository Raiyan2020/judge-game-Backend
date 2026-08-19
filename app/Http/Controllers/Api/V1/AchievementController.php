<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AchievementResource;
use App\Models\Group;
use App\Services\RoleAchievementService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AchievementController extends Controller
{
    public function __construct(protected RoleAchievementService $service) {}

    /**
     * The signed-in user's achievement ladder for [group], scoped to their role
     * in that group. Progress on each rung is computed from live case/judgment
     * counts (`RoleAchievementService`).
     *
     * An unseeded ladder is a legitimate EMPTY result. A thrown exception here
     * (e.g. a not-yet-migrated environment where `role_titles.tier` or the
     * `group_user_titles` table is absent) must NOT hard-500 the whole
     * achievements screen — it degrades to the same empty ladder and is logged
     * server-side so the deploy gap is visible without breaking the app.
     */
    public function index(Group $group)
    {
        try {
            $titles = $this->service->getUserTitles(auth()->user(), $group);
        } catch (Throwable $e) {
            Log::error('Achievements ladder failed; returning empty.', [
                'group_id' => $group->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $titles = collect();
        }

        return \responder::success(AchievementResource::collection($titles));
    }
}
