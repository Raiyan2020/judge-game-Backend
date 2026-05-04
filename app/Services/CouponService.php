<?php

namespace App\Services;

use App\Repositories\CouponRepository;
use Illuminate\Validation\ValidationException;

class CouponService {


    public function __construct(protected CouponRepository $repo)
    {
    }

    public function checkIsValidCoupon($request)
    {
        if (isset($request['code'])) {
        $coupon = $this->repo->checkIsValidCoupon($request['code']);
        throw_unless($coupon, ValidationException::withMessages([__('Coupon Not Available')]));
        return $coupon;
        }
        return false;
    }

    public function create($request)
    {
        return $this->repo->create($request);
    }

    public function update($model ,$request)
    {
        return $this->repo->update($model , $request);
    }

    public function delete($model)
    {
        return $this->repo->delete($model);
    }
    
    public function activation($model)
    {
        return $this->repo->activation($model);
    }

  
}
