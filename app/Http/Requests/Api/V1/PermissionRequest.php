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
           // A role-scoped toggle needs a role; without it (and without a
           // user_id) the service would try to persist a NULL role into a
           // NOT NULL column and 500. Require one of the two targets.
           'role' => ['nullable', 'required_without:user_id', new Enum(\App\Enums\GroupRole::class)],
        ];
    }
}