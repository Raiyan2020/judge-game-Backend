<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BannerType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Services\BannerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function __construct(protected BannerService $bannerService) {}

    /**
     * GET /banners            -> home banners (unchanged behaviour)
     * GET /banners?type=news  -> news screen banners
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'nullable', Rule::enum(BannerType::class)],
        ]);

        $type = BannerType::tryFrom((string) ($validated['type'] ?? '')) ?? BannerType::HOME;

        $banners = $this->bannerService->index($type);
        return \responder::success(BannerResource::collection($banners));
    }
}
