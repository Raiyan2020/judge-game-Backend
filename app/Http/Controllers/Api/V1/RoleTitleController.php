<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGroupUserTitleRequest;
use App\Http\Resources\Api\V1\RoleTitleResource;
use App\Http\Resources\Api\V1\RoleTitleSummaryResource;
use App\Models\Group;
use App\Services\GroupUserTitleService;
use App\Services\RoleAchievementService;
use Illuminate\Http\Request;

class RoleTitleController extends Controller
{
    public function __construct(
        protected RoleAchievementService $service,
        protected GroupUserTitleService $groupUserTitleService
    ) {
    }

    public function index(
        Request $request,
        Group $group
    ) {
        $titles = $this->service
            ->getUserTitles(
                $request->user(),
                $group
            );

        $userSummary = [
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'points' => $request->user()->points?->total_points ?? 0,
            ],
            'current_title' => $titles->firstWhere('used', true)['title'] ?? null,
            'available_titles_count' => $titles->count(),
            'used_titles_count' => $titles->filter(fn ($title) => $title['used'])->count(),
        ];

        return \responder::success([
            'summary' => new RoleTitleSummaryResource($userSummary),
            'titles' => RoleTitleResource::collection($titles),
        ]);
    }

    public function store(StoreGroupUserTitleRequest $request, Group $group)
    {
        $userId = auth()->id();

        $assignment = $this->groupUserTitleService->store(
            $group,
            $userId,
            $request->role_title_id
        );

        return \responder::success(__('Role title assigned successfully'));
    }
}