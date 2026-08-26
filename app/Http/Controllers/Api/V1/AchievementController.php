<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AchievementResource;
use App\Models\Group;
use App\Models\RoleTitle;
use App\Repositories\RoleAchievementRepository;
use App\Services\GroupUserTitleService;
use App\Services\RoleAchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

    /**
     * M4a — set the member's active role title (اللقب) for [group]. The chosen
     * `role_title_id` must exist AND belong to the ladder for the authed user's
     * role in this group (or the shared `all` ladder) — otherwise a member could
     * activate another role's title. Persists a single active title per member.
     */
    public function activateTitle(
        Group $group,
        Request $request,
        RoleAchievementRepository $roles,
        GroupUserTitleService $titles
    ) {
        $data = $request->validate([
            'role_title_id' => ['required', 'integer', 'exists:role_titles,id'],
        ]);

        $user = auth()->user();

        $role = $roles->getUserRoleInGroup($user, $group);
        if (! $role) {
            throw ValidationException::withMessages([
                'role_title_id' => __('You are not a member of this group'),
            ]);
        }

        $belongsToRole = RoleTitle::query()
            ->where('id', $data['role_title_id'])
            ->whereIn('role', [$role, 'all'])
            ->exists();

        if (! $belongsToRole) {
            throw ValidationException::withMessages([
                'role_title_id' => __('This title is not available for your role'),
            ]);
        }

        $titles->setActive($group, (int) $user->id, (int) $data['role_title_id']);

        return \responder::success(__('Active title updated successfully'));
    }
}
