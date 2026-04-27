<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BaseCollection;
use App\Http\Resources\Api\V1\LegalCaseNewsResource;
use App\Services\LegalCaseNewsServices;
use Illuminate\Http\Request;

class LegalCaseNewsController extends Controller
{
     public function __construct(protected  LegalCaseNewsServices $legalCaseNewsService) {}

        public function index(Request $request)
        {
            $legalCaseNews = $this->legalCaseNewsService->index($request->all());
            return \responder::success((new BaseCollection($legalCaseNews, LegalCaseNewsResource::class))->toArray(request()));

        }

   
    
}
