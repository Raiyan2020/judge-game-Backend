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
        $user = auth()->user();
        $this->legalCaseWinLossCounts($user);
        return \responder::success(new UserResource($user->load('activeSubscription.package', 'points')));
    }

    /**
     * Store/refresh the caller's FCM device token so pushes reach them. Called
     * by the app after login and whenever the token rotates.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $user = auth()->user();
        $user->update(['fcm_token' => $request->fcm_token]);
        return \responder::success(__('Token updated'));
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
        $this->legalCaseWinLossCounts($updated);

        return \responder::success(new UserResource($updated->load('activeSubscription.package', 'points')));
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

        $this->legalCaseWinLossCounts($updated);

        return \responder::success(new UserResource($updated->load('activeSubscription.package', 'points')));
    }

    private function legalCaseWinLossCounts($user)
    {
        return $user->loadCount([
            'plaintiffCases',

            'defendantCases',

            'plaintiffCases as plaintiff_wins_count' => function ($q) use ($user) {
                $q->where('winner_id', $user->id);
            },

            'plaintiffCases as plaintiff_losses_count' => function ($q) use ($user) {
                $q->whereNot('winner_id', $user->id)
                    ->whereNotNull('winner_id');
            },

            'defendantCases as defendant_wins_count' => function ($q) use ($user) {
                $q->where('winner_id', $user->id);
            },

            'defendantCases as defendant_losses_count' => function ($q) use ($user) {
                $q->whereNot('winner_id', $user->id)
                    ->whereNotNull('winner_id');
            },
        ]);
    }
}
