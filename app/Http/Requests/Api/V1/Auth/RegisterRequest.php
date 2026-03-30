<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'nickname' => 'nullable|string|max:255',
            'phone' => ['required', Rule::unique('users')->where(function ($query) {
                $query->where('country_code', $this->country_code)->where('phone', $this->phone);
            })],
            'country_code' => 'required|numeric',
            'fcm_token' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'birthdate' => 'nullable|date',
        ];
    }
}
