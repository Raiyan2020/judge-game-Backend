<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['code', 'discount', 'start_at', 'end_at','is_active'])]

class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'date',
            'end_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    #scopes
    public function scopeValidCoupon(Builder $builder)
    {
        $builder->whereIsActive(1)->where('start_at','<=',today())->where('end_at','>=',today());
    }
}
