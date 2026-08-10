<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreHearingRequest;
use App\Http\Resources\Api\V1\HearingResource;
use App\Models\LegalCase;
use App\Services\LegalCaseService;

class HearingController extends Controller
{
    public function __construct(protected LegalCaseService $legalCaseService) {}

    public function index(LegalCase $legalCase)
    {
        $hearings = $this->legalCaseService->listHearings($legalCase);
        return \responder::success(HearingResource::collection($hearings));
    }

    public function store(StoreHearingRequest $request, LegalCase $legalCase)
    {
        $hearing = $this->legalCaseService->scheduleHearing($legalCase, $request->validated());
        return \responder::success(new HearingResource($hearing));
    }
}
