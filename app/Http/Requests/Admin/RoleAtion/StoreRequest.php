<?php

namespace App\Http\Requests\Admin\RoleAtion;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;

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
            'actions' => 'array|min:1',
            'actions.*.id' => 'required|integer|exists:role_actions,id',
            'actions.*.points' => 'required|integer|min:0|max:999999',
        ];
    }
}
