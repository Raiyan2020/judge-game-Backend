<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['package_id', 'user_id', 'coupon_id', 'coupon_code', 'price', 'discount', 'total', 'starts_at', 'ends_at', 'payment_method_id', 'payment_invoice_id', 'payment_status', 'payment_url', 'payment_object'])]
class PackageSubscription extends Model
{
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isActive(): bool
    {
        if (! $this->starts_at || ! $this->starts_at->isBefore(now())) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    #scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    #functions
    public function GeneratePaymentUrl()
    {
        $paymentService = app(\App\Services\Payment\MyfatoorahService::class);
        $this->payment_url = $paymentService->getPaymentUrl($this);
        $this->save();
    }
}
