<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Repositories\RoleAchievementRepository;
use Carbon\Carbon;

class RoleAchievementService
{
    public function __construct(
        protected RoleAchievementRepository $repository
    ) {
    }

    public function getUserTitles(
        User $user,
        Group $group
    ) {
        $role = $this->repository
            ->getUserRoleInGroup(
                $user,
                $group
            );

        if (!$role) {
            return collect();
        }

        $actions = $this->repository
            ->getRoleActions($role);

        $actionCounts = $this->getActionCounts(
            $actions,
            $user,
            $group,
            $role
        );

        $titles = $this->repository
            ->getRoleTitles($role);

        return $this->buildTitles(
            $titles,
            $actionCounts,
            $user,
            $group
        );
    }

    private function getActionCounts(
        $actions,
        User $user,
        Group $group,
        string $role
    ): array {
        $counts = [];

        foreach ($actions as $action) {
            $counts[$action->key] =
                $this->repository->countAction(
                    $action->key,
                    $user,
                    $group,
                    $role
                );
        }

        return $counts;
    }

    private function buildTitles(
        $titles,
        array $actionCounts,
        User $user,
        Group $group
    ) {
        return $titles->map(
            function ($title) use ($actionCounts, $user, $group) {

                $requirements =
                    $title->requirements->map(
                        function ($requirement)
                        use ($actionCounts) {

                            $action =
                                $requirement->action;

                            // Guard an orphaned requirement (role_action_id with
                            // no matching role_actions row — e.g. a partially
                            // seeded DB). Reading $action->key on null 500'd the
                            // whole achievements screen; skip the row instead.
                            if (! $action) {
                                return null;
                            }

                            $current =
                                $actionCounts[
                                    $action->key
                                ] ?? 0;

                            $required =
                                $requirement
                                    ->required_count;

                            return [
                                'id' =>
                                    $requirement->id,

                                'action_key' =>
                                    $action->key,

                                'title' =>
                                    $action->title,

                                'current' =>
                                    $current,

                                'required' =>
                                    $required,

                                'completed' =>
                                    $current >= $required,

                                'percentage' =>
                                    $required > 0
                                        ? min(
                                            100,
                                            (int) (
                                                $current
                                                / $required
                                                * 100
                                            )
                                        )
                                        : 100,
                            ];
                        }
                    )
                    // Drop the orphaned requirements guarded above.
                    ->filter()
                    ->values();

                $usage = app(GroupUserTitleService::class)->getUsage(
                    $group,
                    $user->id,
                    $title->id
                );

                return [
                    'id' => $title->id,

                    'tier' => $title->tier,

                    'reward_points' => $title->reward_points,

                    'title' => $title->title,

                    'requirements' =>
                        $requirements,

                    'completed' =>
                        $requirements->every(
                            fn ($requirement) =>
                                $requirement['completed']
                        ),

                    'used' => $usage['used'],

                    // "used_at" is when the title was CLAIMED (the closest date
                    // signal we store); may be null for a completed-but-unclaimed
                    // rung — the app card tolerates null.
                    'used_at' => $usage['used_at'] ? Carbon::parse($usage['used_at'])->format('d/m/Y') : null  ,
                ];
            }
        );
    }
}