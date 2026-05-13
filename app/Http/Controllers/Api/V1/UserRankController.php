<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserRankingResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserRankController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index(Request $request)
    {
        $result = $this->userService->usersByRoleRank($request);
        return \responder::success(UserRankingResource::collection($result));
    }
}
