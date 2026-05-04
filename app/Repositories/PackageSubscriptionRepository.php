<?php

namespace App\Repositories;

use App\Models\PackageSubscription;

class PackageSubscriptionRepository extends BaseRepository
{
    /**
     * PackageSubscriptionRepository constructor.
     * @param PackageSubscription $model
     */
    public function __construct(PackageSubscription $model)
    {
        parent::__construct($model);
    }

  
}
