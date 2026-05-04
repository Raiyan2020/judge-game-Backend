<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePackageSubscriptionRequest;
use App\Http\Resources\Api\V1\PackageSubscriptionResource;
use App\Services\PackageSubscriptionService;

class PackageSubscriptionController extends Controller
{
    public function __construct(protected PackageSubscriptionService $subscriptionService) {}

    public function subscribe(StorePackageSubscriptionRequest $request)
    {
        $subscription = $this->subscriptionService->subscribeToPackage($request->validated());
        return \responder::success(new PackageSubscriptionResource($subscription));
    }
}
