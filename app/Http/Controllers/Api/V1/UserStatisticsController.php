<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Services\UserStatisticsService;

class UserStatisticsController extends Controller
{
    public function __construct(protected UserStatisticsService $statisticsService) {}

    public function show(Group $group , User $user)
    {
        $result = $this->statisticsService->getForUserGroup($user, $group);
        return \responder::success($result);
    }
}
