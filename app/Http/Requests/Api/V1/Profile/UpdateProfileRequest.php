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
            'phone' => ['required', Rule::unique('users')->where(function ($query) {
                $query->where('country_code', $this->country_code)->where('phone', $this->phone)->where('id', '!=', $this->user()->id);
            })],
            'status' => ['sometimes', 'string', new Enum(\App\Enums\UserStatus::class)],
            'image' => 'sometimes|image|max:2048',
            'country_id' => 'sometimes|exists:countries,id',
        ];
    }
}
