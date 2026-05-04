<?php

namespace App\Services;

use App\Repositories\PackageRepository;
use App\Services\CouponService;
use Illuminate\Validation\ValidationException;

class PackageSubscriptionService
{
    public function __construct(
        protected PackageRepository $repo,
        protected CouponService $couponService
    ) {}

    public function subscribeToPackage(array $data)
    {
        $package = $this->repo->find($data['package_id']);

        if (! $package) {
            throw ValidationException::withMessages([
                'package_id' => __('Package not found'),
            ]);
        }

        if (! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => __('Package is not active'),
            ]);
        }

        $userId = auth()->id();
        $coupon = null;
        $discount = 0;
        $price = $package->price;

        if (! empty($data['coupon_code'])) {
            $couponData = ['code' => $data['coupon_code']];
            $coupon = $this->couponService->checkIsValidCoupon($couponData);

            if ($coupon) {
                $discount = round($price * $coupon->discount / 100, 2);
            }
        }

        $startsAt = now();
        $endsAt = $package->duration_days
            ? now()->addDays($package->duration_days)
            : null;

        $total = $price - $discount;
        $total = max(0, $total);
        

        $subscription = $this->repo->createSubscription(
            $package,
            $userId,
            $startsAt,
            $endsAt,
            $coupon?->id,
            $data['coupon_code'] ?? null,
            $price,
            $discount,
            $total
        );
        $subscription->GeneratePaymentUrl();

        return $subscription->load('package');
    }
}
