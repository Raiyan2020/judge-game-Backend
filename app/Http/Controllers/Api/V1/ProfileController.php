<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UpdateSettingRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * Display the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        return \responder::success(new UserResource($request->user()));
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $updated = $this->userService->updateProfile($user, $request->validated());

        return \responder::success(new UserResource($updated));
    }

    /**
     * Update the authenticated user's settings.
     *
     * @param UpdateSettingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSetting(UpdateSettingRequest $request)
    {
        $user = $request->user();
        $updated = $this->userService->updateSettings($user, $request->validated());

        return \responder::success(new UserResource($updated));
    }
}
