<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdsRequest;
use App\Services\MessageService;

class AdsController extends Controller
{
    public function __construct(protected MessageService $messageService) {}
  

    public function store(AdsRequest $request)
    {
        $message = $this->messageService->CreateAdsPoll($request->validated());
        return \responder::success(__('Ad created successfully'));
    }

}
