<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcceptLegalCaseJudgmentRequest;
use App\Http\Requests\Api\V1\StoreLegalCaseJudgmentRequest;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Services\LegalCaseJudgmentService;

class LegalCaseJudgmentController extends Controller
{
    public function __construct(protected LegalCaseJudgmentService $legalCaseJudgmentService) {}

    public function storeFirstJudgment(StoreLegalCaseJudgmentRequest $request)
    {
        $legalCase = $this->legalCaseJudgmentService->storeFirstJudgment($request->validated());
        $legalCase->load($this->relations());

        return \responder::success(new LegalCaseResource($legalCase));
    }
    public function storeFinalJudgment(StoreLegalCaseJudgmentRequest $request)
    {
        $legalCase = $this->legalCaseJudgmentService->storeFinalJudgment($request->validated());
        $legalCase->load($this->relations());

        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function acceptJudgment(AcceptLegalCaseJudgmentRequest $request)
    {
        $legalCase = $this->legalCaseJudgmentService->acceptJudgment($request->validated());
        $legalCase->load($this->relations());

        return \responder::success(new LegalCaseResource($legalCase));
    }

    private function relations(): array
    {
        return [
            'group',
            'groupLaws',
            'plaintiff.user',
            'defendant.user',
            'defendantLawyer.user',
            'plaintiffLawyer.user',
            'judge.user',
            'witnesses.user',
            'opinions.user',
            'opinions.media',
            'media',
            'judgments',
        ];
    }
}
