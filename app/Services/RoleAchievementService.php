<?php

namespace App\Services;

use App\Models\Group;
use App\Models\PointTransaction;
use App\Models\User;
use App\Repositories\RoleAchievementRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        $built = $this->buildTitles(
            $titles,
            $actionCounts,
            $user,
            $group
        );

        // Lazy settlement: on every read, award the reward_points of any rung the
        // member has newly completed and announce it once (idempotent). Mirrors
        // the project's lazy poll-resolution / execution-auto-close pattern, so
        // rungs completed before this shipped are auto-backfilled on first read.
        $this->settleCompletedRewards(
            $titles,
            $built,
            $user,
            $group,
            $role
        );

        return $built;
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

                    // Whether this is the member's currently-displayed title, so
                    // the app seeds the real active اللقب instead of guessing the
                    // top completed rung (M4a).
                    'is_active' => $usage['is_active'],

                    // "used_at" is when the title was CLAIMED (the closest date
                    // signal we store); may be null for a completed-but-unclaimed
                    // rung — the app card tolerates null.
                    'used_at' => $usage['used_at'] ? Carbon::parse($usage['used_at'])->format('d/m/Y') : null  ,
                ];
            }
        );
    }

    /**
     * Awards each newly-completed rung's `reward_points` and fires the unified
     * `achievement_earned` group event exactly once per rung. Runs on every read
     * (lazy + idempotent), auto-backfilling rungs completed before this shipped.
     *
     * Fail-soft: a write failure here must never break the achievements response,
     * so the whole loop is guarded (and each rung again inside `awardRung`).
     */
    private function settleCompletedRewards(
        $titles,
        $built,
        User $user,
        Group $group,
        string $role
    ): void {
        try {
            $titlesById = $titles->keyBy('id');

            foreach ($built as $row) {
                // `completed` is also true for an EMPTY requirements set
                // (Collection::every() on []), which is exactly the orphaned /
                // unseeded-rung case the display guard tolerates. Never PAY those
                // — gate the award on real, satisfied requirements. The display
                // flag itself is left untouched (AchievementResource::is_completed
                // and RoleTitleController still read that shape).
                if (
                    empty($row['completed'])
                    || count($row['requirements']) === 0
                ) {
                    continue;
                }

                $title = $titlesById->get($row['id']);

                if ($title) {
                    $this->awardRung($title, $user, $group, $role);
                }
            }
        } catch (\Throwable $e) {
            Log::warning(
                'Achievement rewards settle failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Idempotently pays one completed rung and announces it. The stable reason
     * `title_reward:{groupId}:{roleTitleId}:{userId}` is the single marker guarding
     * BOTH the point award and the one-shot group event, so re-reads never
     * double-pay or re-announce.
     */
    private function awardRung(
        $title,
        User $user,
        Group $group,
        string $role
    ): void {
        $rewardPoints = (int) ($title->reward_points ?? 0);

        // A rung still at the migration default (0) has no marker to write, so an
        // ungated event would fire every read — skip both award and announce.
        if ($rewardPoints <= 0) {
            return;
        }

        // `point_transactions.notes` is the idempotency (reason) column.
        $reason = "title_reward:{$group->id}:{$title->id}:{$user->id}";

        try {
            if (PointTransaction::where('notes', $reason)->exists()) {
                return;
            }

            // Build the bilingual announcement BEFORE the award, so a translation
            // read that throws can't burn the marker on a lost event.
            $name = $user->name;
            $titleAr = $title->getTranslation('title', 'ar');
            $titleEn = $title->getTranslation('title', 'en');

            // The `points` view groups by role; a shared ('all') ladder is
            // attributed to the member's group role — always one of the four
            // buckets ('judge'|'lawyer'|'consultant'|'citizen') the view sums.
            $rewardRole = ($title->role && $title->role !== 'all')
                ? $title->role
                : $role;

            app(PointsService::class)->award(
                (int) $user->id,
                $rewardRole,
                $rewardPoints,
                $reason
            );

            $body = [
                'ar' => "حصل {$name} على إنجاز {$titleAr}",
                'en' => "{$name} earned the achievement {$titleEn}",
            ];

            // Fires bell + news + chat once, on the transition only (guarded by the
            // same reason marker above). `actor: $user` named so the call survives
            // the optional `subjectId` param another agent is adding to
            // GroupEventService (we intentionally do NOT pass subjectId yet).
            app(GroupEventService::class)->notifyGroupEvent(
                $group,
                'achievement_earned',
                $body,
                $body,
                actor: $user,
            );
        } catch (\Throwable $e) {
            Log::warning(
                'Achievement reward failed: ' . $e->getMessage()
            );
        }
    }
}