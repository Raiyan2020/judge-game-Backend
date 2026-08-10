<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class GroupMemberInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => 'nullable|string|required_without:username',
            'username' => 'nullable|string|required_without:phone',
            // `judge` is the OWNER's role and is immutable — changeRole already
            // refuses to assign it. Without the same exclusion here, inviting
            // someone as a judge produced a second judge in the group and
            // bypassed that rule entirely.
            'role' => [
                'required',
                'string',
                new Enum(\App\Enums\GroupRole::class),
                Rule::notIn([\App\Enums\GroupRole::JUDGE->value]),
            ],
        ];
    }
}