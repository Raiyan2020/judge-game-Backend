<?php

namespace App\Repositories;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Enums\LegalCaseJudgmentStage;
use App\Models\Group;
use App\Models\LegalCase;
use App\Models\RoleAction;
use App\Models\RoleTitle;
use App\Models\User;

class RoleAchievementRepository
{
    public function getUserRoleInGroup(
        User $user,
        Group $group
    ): ?string {
        return $user->groups()
            ->where('groups.id', $group->id)
            ->first()
            ?->pivot
            ?->role;
    }

    public function getRoleActions(string $role)
    {
        return RoleAction::query()
            ->whereIn('role', [
                $role,
                'all',
            ])
            ->get();
    }

    public function getRoleTitles(string $role)
    {
        return RoleTitle::query()
            ->whereIn('role', [
                $role,
                'all',
            ])
            ->with([
                'requirements.action',
            ])
            ->get();
    }

    public function countAction(
        string $actionKey,
        User $user,
        Group $group,
        string $role
    ): int {
        return match ($actionKey) {

            'acquit_case' =>
                $this->countAcquitCase(
                    $user,
                    $group,
                    $role
                ),

            'first_instance_judgment_case' =>
                $this->countFirstInstanceJudgmentCase(
                    $user,
                    $group,
                    $role
                ),

            'appeal_judgment_case' =>
                $this->countAppealJudgmentCase(
                    $user,
                    $group,
                    $role
                ),

            'close_case_without_judgment' =>
                $this->countCloseCaseWithoutJudgment(
                    $user,
                    $group,
                    $role
                ),

            // 'issue_judgment_instead_of_judge_case' =>
            //     $this->countIssueJudgmentInsteadOfJudge(
            //         $user,
            //         $group,
            //         $role
            //     ),

            'closed_without_judgment_case' =>
                $this->countClosedWithoutJudgmentCase(
                    $user,
                    $group,
                    $role
                ),

            'win_first_instance_case' =>
                $this->countWinFirstInstanceCase(
                    $user,
                    $group,
                    $role
                ),

            'win_appeal_case' =>
                $this->countWinAppealCase(
                    $user,
                    $group,
                    $role
                ),

            'case_closed_with_acquittal' =>
                $this->countCaseClosedWithAcquittal(
                    $user,
                    $group,
                    $role
                ),

            'win_acquittal_case' =>
                $this->countWinAcquittalCase(
                    $user,
                    $group,
                    $role
                ),

            default => 0,
        };
    }

    private function getCaseRoles(
        string $groupRole
    ): array {
        return match ($groupRole) {

            GroupRole::CITIZEN->value => [
                CaseRole::WITNESS->value,
            ],

            GroupRole::LAWYER->value => [
                CaseRole::PLAINTIFF_LAWYER->value,
                CaseRole::DEFENDANT_LAWYER->value,
            ],

            GroupRole::JUDGE->value => [
                CaseRole::JUDGE->value,
            ],

            GroupRole::CONSULTANT->value => [
                CaseRole::CONSULTANT->value,
            ],

            default => [],
        };
    }

    private function baseCaseQuery(
        User $user,
        Group $group,
        string $groupRole
    ) {
        return LegalCase::query()
            ->where(
                'group_id',
                $group->id
            )
            ->whereHas(
                'participants',
                function ($query) use (
                    $user,
                    $groupRole
                ) {
                    $query
                        ->where(
                            'user_id',
                            $user->id
                        )
                        ->whereIn(
                            'role',
                            $this->getCaseRoles(
                                $groupRole
                            )
                        );
                }
            );
    }

    private function countAcquitCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'judgment_type',
                        'acquittal'
                    );
                }
            )
            ->count();
    }

    private function countFirstInstanceJudgmentCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'stage',
                        LegalCaseJudgmentStage::FIRST_INSTANCE->value
                    );
                }
            )
            ->count();
    }

    private function countAppealJudgmentCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'stage',
                        LegalCaseJudgmentStage::APPEAL->value
                    );
                }
            )
            ->count();
    }

    private function countCloseCaseWithoutJudgment(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'judgment_type',
                        'dismissed'
                    );
                }
            )
            ->count();
    }

    private function countIssueJudgmentInsteadOfJudge(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->whereHas(
                        'legalCase',
                        function ($query) {
                            $query->whereHas(
                                'participants',
                                function ($query) {
                                    $query
                                        ->where(
                                            'role',
                                            CaseRole::CONSULTANT->value
                                        )
                                        ->whereColumn(
                                            'legal_case_parties.user_id',
                                            'legal_case_judgments.judged_by'
                                        );
                                }
                            );
                        }
                    );
                }
            )
            ->count();
    }

    private function countClosedWithoutJudgmentCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->countCloseCaseWithoutJudgment(
            $user,
            $group,
            $role
        );
    }

    private function countWinFirstInstanceCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->where(
                'winner_id',
                $user->id
            )
            ->whereHas(
                'firstInstanceJudgment'
            )
            ->count();
    }

    private function countWinAppealCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->where(
                'winner_id',
                $user->id
            )
            ->whereHas(
                'finalJudgment'
            )
            ->count();
    }

    private function countCaseClosedWithAcquittal(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'judgment_type',
                        'acquittal'
                    );
                }
            )
            ->count();
    }

    private function countWinAcquittalCase(
        User $user,
        Group $group,
        string $role
    ): int {
        return $this->baseCaseQuery(
            $user,
            $group,
            $role
        )
            ->where(
                'winner_id',
                $user->id
            )
            ->whereHas(
                'judgments',
                function ($query) {
                    $query->where(
                        'judgment_type',
                        'acquittal'
                    );
                }
            )
            ->count();
    }
}