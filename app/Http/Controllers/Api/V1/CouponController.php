<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Services\CouponService;


class CouponController extends Controller
{

    public function __construct(protected CouponService $couponService)
    {
    }

     /**
     * @return \Illuminate\Http\JsonResponse
     */


    public function store(CouponRequest $request)
    { 
     $validCoupon = $this->couponService->checkIsValidCoupon($request);
     
     return \responder::success(new CouponResource($validCoupon));


    }
}
