<?php

namespace App\Repositories;

use App\Models\Coupon;

class CouponRepository extends BaseRepository
{
  /**
   * CouponRepository constructor.
   * @param Coupon $model
   */
  public function __construct(Coupon $model)
  {
    parent::__construct($model);
  }

  public function checkIsValidCoupon($code)
  {
    return $this->model->where('code',$code)->validCoupon($code)->first();
  }

 
}
