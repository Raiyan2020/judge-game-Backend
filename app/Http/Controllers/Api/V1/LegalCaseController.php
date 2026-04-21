<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LegalCaseRequest;
use App\Services\LegalCaseService;

class LegalCaseController extends Controller
{
    public function __construct(protected  LegalCaseService $legalCaseService)
    {
    }

    public function store(LegalCaseRequest $request)
    {
         $this->legalCaseService->create($request->validated());

        return \responder::success(__('Legal case created successfully'));
    }
}