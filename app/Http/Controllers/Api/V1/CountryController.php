<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\CountryService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CountryResource;

class CountryController extends Controller
{

    public function __construct(protected CountryService $countryService)
    {
    }

     /**
     * @return \Illuminate\Http\JsonResponse
     */

   public function __invoke()
   {
      return  \responder::success(CountryResource::collection($this->countryService->index()));
   }
}
