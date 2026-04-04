<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait IsActive
{
    public function scopeActive(Builder $builder)
    {
        $builder->whereIsActive(1);

    }
}
