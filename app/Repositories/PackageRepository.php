<?php

namespace App\Repositories;

use App\Models\Package;

class PackageRepository extends BaseRepository
{
    /**
     * PackageRepository constructor.
     * @param Package $model
     */
    public function __construct(Package $model)
    {
        parent::__construct($model);
    }

    public function getActivePackages()
    {
        return $this->model->where('is_active', true)->get();
    }

    public function createSubscription($package, $userId, $startsAt, $endsAt, $couponId = null, $code = null, $price = 0, $discount = 0, $total = 0)
    {
        return $package->subscriptions()->create([
            'user_id' => $userId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'coupon_id' => $couponId,
            'coupon_code' => $code,
            'price' => $price,
            'discount' => $discount,
            'total' => $total,
        ]);
    }
}
