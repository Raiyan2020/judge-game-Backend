<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\LastUpdateResource;
use App\Http\Resources\Api\V1\TipResource;
use App\Services\BannerService;
use App\Services\LastUpdateService;
use App\Services\TipService;

class HomeController extends Controller
{
    public function __construct(
        protected BannerService $bannerService,
        protected TipService $tipService,
        protected LastUpdateService $lastUpdateService
    ) {}

    public function __invoke()
    {
        $limit = 10;
        $data['banners']  = BannerResource::collection($this->bannerService->index());
        $data['tips']  = TipResource::collection($this->tipService->index()); 
        $data['last_updates']  = LastUpdateResource::collection($this->lastUpdateService->index($limit));  
        return  \responder::success($data);
    }
}
