<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BannerType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\BaseCollection;
use App\Http\Resources\Api\V1\LegalCaseNewsResource;
use App\Services\BannerService;
use App\Services\LegalCaseNewsServices;
use Illuminate\Http\Request;

class LegalCaseNewsController extends Controller
{
     public function __construct(
         protected LegalCaseNewsServices $legalCaseNewsService,
         protected BannerService $bannerService
     ) {}

        public function index(Request $request)
        {
            $legalCaseNews = $this->legalCaseNewsService->index($request->all());

            // The news screen's banners ride along with the news list itself, so
            // the app does not need a second call — and adding a banner of type
            // `news` in the dashboard shows up here right away. Sent on every
            // page so paging the list never blanks the banner strip.
            $extra = [
                'banners' => BannerResource::collection(
                    $this->bannerService->index(BannerType::NEWS)
                ),
            ];

            return \responder::success((new BaseCollection($legalCaseNews, LegalCaseNewsResource::class, $extra))->toArray(request()));

        }
}
