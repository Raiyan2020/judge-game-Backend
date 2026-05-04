<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_url' => $this->payment_url,
            'package' => new PackageResource($this->whenLoaded('package')),
            'coupon_code' => $this->coupon_code,
            'price' => $this->price,
            'discount' => $this->discount,
            'total' => $this->total,
            'starts_at' => $this->starts_at->format('Y-m-d H:i:s'),
            'ends_at' => $this->ends_at?->format('Y-m-d H:i:s') ?? __('unlimited'),
           
        ];
    }
}
