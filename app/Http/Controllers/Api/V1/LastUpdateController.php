<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LastUpdateResource;
use App\Services\LastUpdateService;

class LastUpdateController extends Controller
{
    public function __construct(protected LastUpdateService $lastUpdateService)
    {
    }

    public function __invoke()
    {
        return \responder::success(LastUpdateResource::collection($this->lastUpdateService->index()));
    }
}