<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Services\BannerService;

class BannerController extends Controller
{
    public function __construct(protected BannerService $bannerService) {}

    public function index()
    {
        $banners = $this->bannerService->index();
        return \responder::success(BannerResource::collection($banners));
    }
}
