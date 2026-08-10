<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'country_code' => 'sometimes|numeric',
            // `phone` is accepted but NOT applied — see UserService::updateProfile.
            // Changing the number here proved no ownership of it: a typo locked
            // the user out of their own account permanently (login is by phone),
            // and an unregistered number belonging to someone else could be
            // claimed. It now moves only through the verified two-step flow
            // (`/auth/phone-change/request` → `/confirm`).
            //
            // Kept as a rule rather than dropped so existing app builds, which
            // always send it, keep validating instead of 422-ing.
            'phone' => ['sometimes'],
            'status' => ['sometimes', 'string', new Enum(\App\Enums\UserStatus::class)],
            'image' => 'sometimes|image|max:2048',
            'country_id' => 'sometimes|exists:countries,id',
        ];
    }
}
