<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignDefendantLawyerRequest;
use App\Http\Requests\Api\V1\LegalCaseRequest;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\LegalCaseService;
use PhpParser\Node\Expr\Assign;

class LegalCaseController extends Controller
{
    public function __construct(protected  LegalCaseService $legalCaseService) {}

    public function store(LegalCaseRequest $request)
    {
        $this->legalCaseService->create($request->validated());

        return \responder::success(__('Legal case created successfully'));
    }

    public function show(LegalCase $legalCase)
    {
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
        ];
    }
}
