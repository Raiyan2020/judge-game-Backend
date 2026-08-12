<?php

namespace App\Http\Requests\Admin\RoleTitle;

use App\Enums\GroupRole;
use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */

    public function rules(): array
    {
        return [
            'title' => 'required|array',
            'title.ar' => 'required|string|min:1|max:500',
            'title.en' => 'required|string|min:1|max:500',
            'role' => ['required', 'string', 'max:50', new Enum(GroupRole::class)],
            'actions' => 'required|array|min:1',
            'actions.*.role_action_id' => 'required|integer|exists:role_actions,id',
            'actions.*.required_count' => 'required|integer|min:1|max:999999',
        ];
    }
}
