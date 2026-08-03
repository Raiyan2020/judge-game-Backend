<?php

namespace App\Repositories;

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

    public function index($filters)
    {
        $query = $this->model->query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        return $query->with(['plaintiff', 'defendant', 'group', 'plaintiffLawyer', 'defendantLawyer', 'finalJudgment'])->latest()->paginate(10);
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

    public function getCasesStatus($groupId = null)
    {
      return $this->model
        ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
        ->selectRaw("
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new_cases,
            COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as on_going_cases,
            COUNT(CASE WHEN status = 'appeal' THEN 1 END) as appeal_cases,
            COUNT(CASE WHEN status = 'execution' THEN 1 END) as execution_cases,
            COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases
        ")
        ->first();
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

    public function getUserGroupStatistics(int $userId, int $groupId): array
    {
        $userCasesQuery = $this->model->newQuery()
            ->where('group_id', $groupId)
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });

        $raisedCases = $this->model->newQuery()
            ->where('group_id', $groupId)
            ->whereHas('plaintiff', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        $defenseCases = $this->model->newQuery()
            ->where('group_id', $groupId)
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
        ];
    }
}
