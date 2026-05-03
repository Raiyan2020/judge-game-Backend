<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLegalCaseAppealRequest;
use App\Http\Requests\Api\V1\StoreLegalCaseOpinionRequest;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Services\LegalCaseOpinionServices;

class LegalCaseOpinionController extends Controller
{
    public function __construct(protected  LegalCaseOpinionServices $legalCaseOpinionService) {}

    public function addOpinion(StoreLegalCaseOpinionRequest $request)
    {
        $legalCase = $this->legalCaseOpinionService->createOpinion($request->validated());
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function requestAppeal(StoreLegalCaseAppealRequest $request)
    {
        $legalCase = $this->legalCaseOpinionService->requestAppeal($request->validated());
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
            'finalJudgment',
            'firstInstanceJudgment',
        ];
    }
}
