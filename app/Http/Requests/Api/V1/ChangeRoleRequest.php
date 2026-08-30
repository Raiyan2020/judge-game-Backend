<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GroupRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Only the three assignable personas — the JUDGE is the group owner and
        // is NOT assignable, so a group can never have a second judge. Rejecting
        // `judge` here (instead of accepting the whole GroupRole enum) is what
        // turns the raw "the role field is invalid" into the clear message below.
        return [
            'role' => ['required', Rule::in([
                GroupRole::CITIZEN->value,
                GroupRole::LAWYER->value,
                GroupRole::CONSULTANT->value,
            ])],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => __('You cannot assign another judge — a group has a single judge (its owner). Choose citizen, lawyer, or consultant.'),
            'role.required' => __('Please choose a role.'),
        ];
    }
}