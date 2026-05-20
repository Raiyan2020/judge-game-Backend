<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email:dns|unique:admins,email,' . object_get($this->route('admin'), 'id') . ',id',
            'password' => 'nullable|string|min:6',
            'phone'=>'required|string|min:6|max:20|unique:admins,phone,' . object_get($this->route('admin'), 'id') . ',id',
            'is_active'=>'required|in:0,1',


        ];
    }
}
