<?php

namespace App\Repositories;

use App\Enums\CaseRole;
use App\Enums\LegalCaseJudgmentStage;
use App\Enums\LegalCaseJudgmentType;
use App\Enums\LegalCaseStatus;
use App\Models\LegalCase;
use App\Models\LegalCaseJudgment;

class LegalCaseRepository extends BaseRepository
{
    /**
     * LegalCaseRepository constructor.
     * @param LegalCase $model
     */
    public function __construct(LegalCase $model)
    {
        parent::__construct($model);
    }

    /**
     * Row-locked fetch for use inside a transaction. Serialises concurrent
     * plaintiff-lawyer submissions so two of them cannot both read the case as
     * `pending_lawyer` and double-fire the case-filed effects (there is no
     * unique constraint to lean on).
     */
    public function findForUpdate($id)
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    public function index($filters)
    {
        $query = $this->model->query();

        // A `pending_lawyer` case is officially unfiled and must be visible ONLY
        // to its assigned plaintiff lawyer (the «بانتظار رفعي» bucket). Applied
        // UNCONDITIONALLY — before the status filter — so it also covers a
        // no-status listing of the group, not just an explicit
        // `status=pending_lawyer` request. Fails closed when no user id is
        // passed (matches nothing pending).
        $currentUserId = $filters['current_user_id'] ?? 0;
        $query->where(function ($q) use ($currentUserId) {
            $q->where('status', '!=', LegalCaseStatus::PENDING_LAWYER->value)
                ->orWhereHas('participants', function ($p) use ($currentUserId) {
                    $p->where('user_id', $currentUserId)
                        ->where('role', CaseRole::PLAINTIFF_LAWYER->value);
                });
        });

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        return $query->with(['plaintiff', 'defendant', 'group', 'plaintiffLawyer', 'defendantLawyer', 'finalJudgment', 'media'])->latest()->paginate(10);
    }

    public function createCaseNews($legalCase, $type, $content, $actorId , $subjectId )
    {
         $news = $legalCase->news()->create([
            'type' => $type,
            'content' => $content,
            'actor_id' => $actorId,
            'group_id' => $legalCase->group_id,
            'subject_id' => $subjectId,
        ]);
        return $news;
    }

    public function getCasesStatus($groupId = null, $userId = null)
    {
      $counts = $this->model
        ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
        ->selectRaw("
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new_cases,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_cases,
            COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as on_going_cases,
            COUNT(CASE WHEN status = 'appeal' THEN 1 END) as appeal_cases,
            COUNT(CASE WHEN status = 'execution' THEN 1 END) as execution_cases,
            COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases
        ")
        ->first();

      // The «بانتظار رفعي» counter is USER-scoped, not a group-wide tally: a
      // `pending_lawyer` case is only ever visible to its assigned plaintiff
      // lawyer, so this count is restricted to cases where the caller is that
      // lawyer. Computed as a separate query (a participant-scoped conditional
      // count can't live in the group-wide selectRaw) and merged as a dynamic
      // attribute so it serializes alongside the others.
      $pendingCount = 0;
      if ($userId) {
          $pendingCount = $this->model->newQuery()
              ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
              ->where('status', LegalCaseStatus::PENDING_LAWYER->value)
              ->whereHas('participants', function ($q) use ($userId) {
                  $q->where('user_id', $userId)
                    ->where('role', CaseRole::PLAINTIFF_LAWYER->value);
              })
              ->count();
      }
      $counts->pending_lawyer_cases = $pendingCount;

      return $counts;
    }

    /**
     * Close every case that has sat in `execution` for the full enforcement
     * window (final judgment ≥ 7 days old). This is the SAME rule the
     * `CloseExpiredExecutionCases` job runs — extracted here so it can also be
     * triggered lazily on read (the scheduler is optional infrastructure), so
     * a case that goes the full distance actually reaches `closed` instead of
     * being stuck in `execution` forever.
     */
    public function closeExpiredExecutionCases($groupId = null): int
    {
        return $this->model->query()
            ->where('status', LegalCaseStatus::EXECUTION->value)
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->whereHas('finalJudgment', function ($query) {
                $query->where('created_at', '<=', now()->subDays(7));
            })
            ->update(['status' => LegalCaseStatus::CLOSED->value]);
    }

    /**
     * The auto-uphold window (BUG9, product decision): a first-instance verdict
     * left un-appealed for this long is upheld automatically. Sits beside the
     * 7-day execution window above so both time-based settlement rules live in
     * one place.
     */
    public const APPEAL_WINDOW_HOURS = 24;

    /**
     * Cases whose first-instance verdict has stood past the appeal window with
     * NO appeal — i.e. still `ongoing` (requesting an appeal flips the status to
     * `appeal`, so an ongoing case by definition has no appeal) and carrying a
     * first-instance judgment created at least APPEAL_WINDOW_HOURS ago.
     *
     * Returns the models (not a bulk UPDATE) because upholding must mirror
     * `acceptJudgment` per case — winner_id + is_final + points — which a single
     * UPDATE cannot express. Only convictions reach here: acquittal/dismissed
     * first judgments close immediately as final and never sit in `ongoing`.
     */
    public function expiredUnappealedFirstInstanceCases($groupId = null)
    {
        return $this->model->query()
            ->where('status', LegalCaseStatus::ONGOING->value)
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->whereHas('firstInstanceJudgment', function ($query) {
                $query->where('created_at', '<=', now()->subHours(self::APPEAL_WINDOW_HOURS));
            })
            ->with(['plaintiff', 'defendant', 'plaintiffLawyer', 'firstInstanceJudgment', 'group'])
            ->get();
    }

    /**
     * A user's case record ACROSS every group, scoped to one judicial role —
     * what the ranks leaderboard's detail sheet shows.
     *
     * Role-scoped because the boards are: the same person is a judge on one
     * board and a citizen on another, and the numbers must match the board you
     * opened. Note `acquittal_judgments` counts by judgment TYPE, so it overlaps
     * the two stage counters rather than summing with them.
     */
    public function getUserCaseStatsByRole(int $userId, string $groupRole): array
    {
        $caseRoles = match ($groupRole) {
            'judge'      => [CaseRole::JUDGE->value],
            'consultant' => [CaseRole::CONSULTANT->value],
            'lawyer'     => [CaseRole::PLAINTIFF_LAWYER->value, CaseRole::DEFENDANT_LAWYER->value],
            'citizen'    => [CaseRole::PLAINTIFF->value, CaseRole::DEFENDANT->value],
            default      => [],
        };

        $participated = function ($query) use ($userId, $caseRoles) {
            $query->where('user_id', $userId)->whereIn('role', $caseRoles);
        };

        $judgmentQuery = LegalCaseJudgment::query()
            ->whereHas('legalCase', function ($query) use ($participated) {
                $query->whereHas('participants', $participated);
            });

        // Legacy 4 fields — kept so an OLD app build still parses something.
        $legacy = [
            'participated_cases' => $this->model->newQuery()
                ->whereHas('participants', $participated)
                ->count(),
            'first_instance_judgments' => (clone $judgmentQuery)
                ->where('stage', LegalCaseJudgmentStage::FIRST_INSTANCE->value)->count(),
            'appeal_judgments' => (clone $judgmentQuery)
                ->where('stage', LegalCaseJudgmentStage::APPEAL->value)->count(),
            'acquittal_judgments' => (clone $judgmentQuery)
                ->where('judgment_type', LegalCaseJudgmentType::ACQUITTAL->value)->count(),
        ];

        // Role-specific tile set (JG-008/JG-017): the app renders whatever tiles
        // arrive, so each role shows the counters that matter to it. `key` is an
        // l10n key the app resolves.
        return array_merge($legacy, [
            'tiles' => $this->roleTiles($userId, $groupRole, $caseRoles),
        ]);
    }

    /**
     * The ordered, role-appropriate statistic tiles for a user.
     * @return array<int, array{key:string, value:int}>
     */
    private function roleTiles(int $userId, string $groupRole, array $caseRoles): array
    {
        // Cases the user is a party to under this role.
        $asRole = fn ($caseRole) => function ($q) use ($userId, $caseRole) {
            $q->where('user_id', $userId)->where('role', $caseRole);
        };
        $countAs = fn ($caseRole) => $this->model->newQuery()
            ->whereHas('participants', $asRole($caseRole))->count();

        // Cases (under any of this role's case-roles) the user WON / lost.
        $participated = function ($q) use ($userId, $caseRoles) {
            $q->where('user_id', $userId)->whereIn('role', $caseRoles);
        };
        $wins = $this->model->newQuery()
            ->whereHas('participants', $participated)
            ->where('winner_id', $userId)->count();
        $decided = $this->model->newQuery()
            ->whereHas('participants', $participated)
            ->whereNotNull('winner_id')->count();
        $losses = max(0, $decided - $wins);
        $opinions = \App\Models\LegalCaseOpinion::where('user_id', $userId)->count();

        return match ($groupRole) {
            'citizen' => [
                ['key' => 'ranks_stats_cases_filed', 'value' => $countAs(CaseRole::PLAINTIFF->value)],
                ['key' => 'ranks_stats_cases_against', 'value' => $countAs(CaseRole::DEFENDANT->value)],
                ['key' => 'ranks_stats_wins', 'value' => $wins],
                ['key' => 'ranks_stats_losses', 'value' => $losses],
            ],
            'lawyer' => [
                ['key' => 'ranks_stats_cases_represented', 'value' => (int) $this->model->newQuery()
                    ->whereHas('participants', $participated)->count()],
                ['key' => 'ranks_stats_wins', 'value' => $wins],
                ['key' => 'ranks_stats_losses', 'value' => $losses],
                ['key' => 'ranks_stats_opinions', 'value' => $opinions],
            ],
            'judge' => [
                ['key' => 'ranks_stats_cases_judged', 'value' => $countAs(CaseRole::JUDGE->value)],
                ['key' => 'ranks_stats_first_instance', 'value' => LegalCaseJudgment::where('judged_by', $userId)
                    ->where('stage', LegalCaseJudgmentStage::FIRST_INSTANCE->value)->count()],
                ['key' => 'ranks_stats_appeal', 'value' => LegalCaseJudgment::where('judged_by', $userId)
                    ->where('stage', LegalCaseJudgmentStage::APPEAL->value)->count()],
                ['key' => 'ranks_stats_closed', 'value' => $this->model->newQuery()
                    ->whereHas('participants', $asRole(CaseRole::JUDGE->value))
                    ->where('status', LegalCaseStatus::CLOSED->value)->count()],
            ],
            'consultant' => [
                ['key' => 'ranks_stats_consultations', 'value' => $countAs(CaseRole::CONSULTANT->value)],
                ['key' => 'ranks_stats_opinions', 'value' => $opinions],
            ],
            default => [],
        };
    }

    public function getUserGroupStatistics(int $userId, int $groupId): array
    {
        $userCasesQuery = $this->model->newQuery()
            ->where('group_id', $groupId)
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });

        $raisedCases = $this->model->newQuery()
            ->where('group_id', $groupId)
            // A `pending_lawyer` case is not yet officially filed (hidden from the
            // parties), so it must not leak a "+1 hidden case" into these tallies.
            ->where('status', '!=', LegalCaseStatus::PENDING_LAWYER->value)
            ->whereHas('plaintiff', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        $defenseCases = $this->model->newQuery()
            ->where('group_id', $groupId)
            ->where('status', '!=', LegalCaseStatus::PENDING_LAWYER->value)
            ->where(function ($query) use ($userId) {
                $query->whereHas('defendant', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->orWhereHas('defendantLawyer', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                });
            })
            ->count();

        $judgmentQuery = LegalCaseJudgment::query()
            ->whereHas('legalCase', function ($query) use ($groupId, $userId) {
                $query->where('group_id', $groupId)
                    ->whereHas('participants', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            });

        return [
            'closed_cases' => (clone $userCasesQuery)->where('status', LegalCaseStatus::CLOSED->value)->count(),
            'raised_cases' => $raisedCases,
            'defense_cases' => $defenseCases,
            'ongoing_cases' => (clone $userCasesQuery)->where('status', LegalCaseStatus::ONGOING->value)->count(),
            'execution_cases' => (clone $userCasesQuery)->where('status', LegalCaseStatus::EXECUTION->value)->count(),
            'first_instance_judgments' => (clone $judgmentQuery)->where('stage', LegalCaseJudgmentStage::FIRST_INSTANCE->value)->count(),
            'appeal_judgments' => (clone $judgmentQuery)->where('stage', LegalCaseJudgmentStage::APPEAL->value)->count(),
            'acquittal_judgments' => (clone $judgmentQuery)->where('judgment_type', LegalCaseJudgmentType::ACQUITTAL->value)->count(),
            // Role-specific tiles scoped to THIS group (JG-017), so the member
            // card shows the counters that fit their role, not one generic set.
            'tiles' => $this->groupRoleTiles($userId, $groupId),
        ];
    }

    /**
     * Group-scoped, role-appropriate tiles for a member's statistics card.
     * @return array<int, array{key:string, value:int}>
     */
    private function groupRoleTiles(int $userId, int $groupId): array
    {
        $role = \DB::table('group_user')
            ->where('group_id', $groupId)->where('user_id', $userId)
            ->value('role');
        // The owner is always the judge, whatever the stored role says.
        $ownerId = \App\Models\Group::whereKey($groupId)->value('user_id');
        if ((int) $ownerId === $userId) {
            $role = 'judge';
        }

        $inGroupAs = fn ($caseRole) => $this->model->newQuery()
            ->where('group_id', $groupId)
            // Exclude not-yet-filed `pending_lawyer` cases from role tallies for
            // the same visibility reason as above.
            ->where('status', '!=', LegalCaseStatus::PENDING_LAWYER->value)
            ->whereHas('participants', function ($q) use ($userId, $caseRole) {
                $q->where('user_id', $userId)->where('role', $caseRole);
            })->count();

        $wins = $this->model->newQuery()->where('group_id', $groupId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->where('winner_id', $userId)->count();
        $decided = $this->model->newQuery()->where('group_id', $groupId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('winner_id')->count();
        $losses = max(0, $decided - $wins);
        $opinions = \App\Models\LegalCaseOpinion::where('user_id', $userId)
            ->whereHas('legalCase', fn ($q) => $q->where('group_id', $groupId))
            ->count();

        return match ($role) {
            'citizen' => [
                ['key' => 'ranks_stats_cases_filed', 'value' => $inGroupAs(CaseRole::PLAINTIFF->value)],
                ['key' => 'ranks_stats_cases_against', 'value' => $inGroupAs(CaseRole::DEFENDANT->value)],
                ['key' => 'ranks_stats_wins', 'value' => $wins],
                ['key' => 'ranks_stats_losses', 'value' => $losses],
            ],
            'lawyer' => [
                ['key' => 'ranks_stats_cases_represented', 'value' => $inGroupAs(CaseRole::PLAINTIFF_LAWYER->value) + $inGroupAs(CaseRole::DEFENDANT_LAWYER->value)],
                ['key' => 'ranks_stats_wins', 'value' => $wins],
                ['key' => 'ranks_stats_losses', 'value' => $losses],
                ['key' => 'ranks_stats_opinions', 'value' => $opinions],
            ],
            'judge' => [
                ['key' => 'ranks_stats_cases_judged', 'value' => $inGroupAs(CaseRole::JUDGE->value)],
                ['key' => 'ranks_stats_first_instance', 'value' => LegalCaseJudgment::where('judged_by', $userId)
                    ->whereHas('legalCase', fn ($q) => $q->where('group_id', $groupId))
                    ->where('stage', LegalCaseJudgmentStage::FIRST_INSTANCE->value)->count()],
                ['key' => 'ranks_stats_appeal', 'value' => LegalCaseJudgment::where('judged_by', $userId)
                    ->whereHas('legalCase', fn ($q) => $q->where('group_id', $groupId))
                    ->where('stage', LegalCaseJudgmentStage::APPEAL->value)->count()],
                ['key' => 'ranks_stats_closed', 'value' => $this->model->newQuery()->where('group_id', $groupId)
                    ->whereHas('participants', function ($q) use ($userId) {
                        $q->where('user_id', $userId)->where('role', CaseRole::JUDGE->value);
                    })->where('status', LegalCaseStatus::CLOSED->value)->count()],
            ],
            'consultant' => [
                ['key' => 'ranks_stats_consultations', 'value' => $inGroupAs(CaseRole::CONSULTANT->value)],
                ['key' => 'ranks_stats_opinions', 'value' => $opinions],
            ],
            default => [],
        };
    }
}
