<?php

namespace App\Services;

use App\Models\PackageSubscription;
use App\Repositories\PackageRepository;
use App\Services\CouponService;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function __construct(
        protected PackageRepository $repo,
        protected CouponService $couponService
    ) {}

    public function getActivePackages()
    {
        return $this->repo->getActivePackages();
    }

    public function getCurrentSubscription()
    {
        return auth()->user()->activeSubscription()->with('package')->first();
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