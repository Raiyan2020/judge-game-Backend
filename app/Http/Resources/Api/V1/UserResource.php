<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'token' => $this->token,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'username' => $this->username,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'full_phone' => $this->full_phone,
            'image' => $this->image,
            'gender' => $this->gender,
            'language' => $this->language,
            'notified' => $this->notified,
            'birthdate' => $this->birthdate,
            'status' => $this->status,
            'status_text' => __($this->status),
            'member_since' => $this->created_at->locale(app()->getLocale())->translatedFormat('j-F-Y'),
        ];
    }
}
