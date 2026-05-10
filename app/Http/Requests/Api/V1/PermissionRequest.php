<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           'group_id' => ['required', 'exists:groups,id'],
           'user_id' => ['nullable', 'exists:users,id'],
           'permission_id' => ['required', 'exists:permissions,id'],
           'role' => ['nullable', new Enum(\App\Enums\GroupRole::class)],
        ];
    }
}