<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserCaseStatsResource;
use App\Http\Resources\Api\V1\UserRankingResource;
use App\Models\User;
use App\Repositories\LegalCaseRepository;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserRankController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected LegalCaseRepository $legalCaseRepository
    ) {}

    public function index(Request $request)
    {
        $result = $this->userService->usersByRoleRank($request);
        return \responder::success(UserRankingResource::collection($result));
    }

    /**
     * One competitor's case record, for the leaderboard's detail sheet.
     *
     * The app has always called this (`GET /users-ranking/{id}?role=`) but it
     * was never registered, so the sheet could only ever show an error.
     */
    public function show(Request $request, User $user)
    {
        // Required, and validated rather than typed: the counts are role-scoped,
        // so without a role there is no meaningful answer to give.
        $request->validate([
            'role' => 'required|in:judge,consultant,lawyer,citizen',
        ]);

        $user->setAttribute(
            'case_stats',
            $this->legalCaseRepository->getUserCaseStatsByRole($user->id, $request->role)
        );

        return \responder::success(new UserCaseStatsResource($user->load('country')));
    }
}
