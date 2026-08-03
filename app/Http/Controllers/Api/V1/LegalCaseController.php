<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignDefendantLawyerRequest;
use App\Http\Requests\Api\V1\LegalCaseRequest;
use App\Http\Resources\Api\V1\BaseCollection;
use App\Http\Resources\Api\V1\LegalCaseListResource;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\LegalCaseService;
use Illuminate\Http\Request;

class LegalCaseController extends Controller
{
    public function __construct(protected  LegalCaseService $legalCaseService) {}

    public function index(Request $request, $group)
    {
        $legalCases = $this->legalCaseService->index($request->all(), $group);
        return \responder::success((new BaseCollection($legalCases, LegalCaseListResource::class))->toArray(request()));

    }

    public function getCaseStatus(Request $request)
    {
        $legalCases = $this->legalCaseService->getCasesStatus($request->group_id);
        return \responder::success($legalCases);
    }

    public function store(LegalCaseRequest $request)
    {
        $legalCase = $this->legalCaseService->create($request->validated());
        $legalCase->load($this->relations());

        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function show(LegalCase $legalCase)
    {
        // A case that finished its enforcement window should read as `closed`.
        $this->legalCaseService->settleIfExecutionExpired($legalCase);
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function assignDefendantLawyer(AssignDefendantLawyerRequest $request)
    {
        $legalCase = $this->legalCaseService->assignDefendantLawyer($request->validated());
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
