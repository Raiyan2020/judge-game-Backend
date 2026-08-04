<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AchievementResource;
use App\Models\Group;
use App\Services\RoleAchievementService;

class AchievementController extends Controller
{
    public function __construct(protected RoleAchievementService $service) {}

    /**
     * The signed-in user's achievement ladder for [group], scoped to their role
     * in that group. Progress on each rung is computed from live case/judgment
     * counts (`RoleAchievementService`).
     */
    public function index(Group $group)
    {
        $titles = $this->service->getUserTitles(auth()->user(), $group);
        return \responder::success(AchievementResource::collection($titles));
    }
}
