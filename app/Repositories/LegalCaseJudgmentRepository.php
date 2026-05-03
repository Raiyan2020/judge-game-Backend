<?php

namespace App\Repositories;

use App\Models\LegalCaseJudgment;

class LegalCaseJudgmentRepository extends BaseRepository
{
    public function __construct(LegalCaseJudgment $model)
    {
        parent::__construct($model);
    }

    public function firstInstanceForCase(int $legalCaseId)
    {
        return $this->model
            ->where('legal_case_id', $legalCaseId)
            ->where('stage', \App\Enums\LegalCaseJudgmentStage::FIRST_INSTANCE->value)
            ->first();
    }

    public function hasStageForCase(int $legalCaseId, string $stage): bool
    {
        return $this->model
            ->where('legal_case_id', $legalCaseId)
            ->where('stage', $stage)
            ->exists();
    }
}
