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
        // Block a new subscription while the user already has an active (paid,
        // unexpired) one — the app had no such guard, so re-subscribing just
        // stacked another row.
        if (auth()->user()?->activeSubscription()->exists()) {
            throw ValidationException::withMessages([
                'package_id' => __('You already have an active subscription.'),
            ]);
        }

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

        // Reuse a RECENT still-pending subscription for the same package instead
        // of minting another row + another live invoice — repeated taps would
        // otherwise let the user be charged twice. Bounded to a short window: a
        // MyFatoorah invoice URL expires, so reusing an old one would lock the
        // user out of ever getting a fresh one. Only reuse when the terms match
        // (same coupon), else fall through and create a fresh invoice.
        $pending = \App\Models\PackageSubscription::where('user_id', auth()->id())
            ->where('package_id', $package->id)
            ->whereNull('payment_status')
            ->whereNotNull('payment_url')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->where('coupon_code', $data['coupon_code'] ?? null)
            ->latest()
            ->first();
        if ($pending) {
            return $pending->load('package');
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
        // A null/0 duration means `ends_at = null`, which `activeSubscription()`
        // treats as NEVER expiring — one payment buys permanent access. Left as
        // intended-lifetime behaviour, but logged so an accidentally-empty
        // duration on a paid package is visible rather than silent.
        if (empty($package->duration_days)) {
            \Illuminate\Support\Facades\Log::warning(
                'Package ' . $package->id . ' has no duration_days — subscription will never expire.'
            );
        }
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
