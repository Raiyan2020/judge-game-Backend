<?php

namespace App\Http\Requests\Admin\Admin;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email:dns|max:255|unique:admins,email,' . object_get($this->route('admin'), 'id') . ',id',
            'password' => 'nullable|string|min:6|max:255',
            'phone' => 'required|string|min:6|max:20|unique:admins,phone,' . object_get($this->route('admin'), 'id') . ',id',
            'is_active' => 'required|in:0,1',
        ];
    }
}
