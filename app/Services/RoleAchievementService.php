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
                    );

                $usage = app(GroupUserTitleService::class)->getUsage(
                    $group,
                    $user->id,
                    $title->id
                );

                return [
                    'id' => $title->id,

                    'title' => $title->title,

                    'requirements' =>
                        $requirements,

                    'completed' =>
                        $requirements->every(
                            fn ($requirement) =>
                                $requirement['completed']
                        ),

                    'used' => $usage['used'],

                    'used_at' => $usage['used_at'] ? Carbon::parse($usage['used_at'])->format('Y-m-d') : null  ,
                ];
            }
        );
    }
}