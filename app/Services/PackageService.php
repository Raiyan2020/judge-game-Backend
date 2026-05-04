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
}