<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
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
            'phone' => 'sometimes|string|max:50',
            'status' => ['sometimes', 'string', new Enum(\App\Enums\UserStatus::class)],
            'image' => 'sometimes|image|max:2048',
        ];
    }
}
