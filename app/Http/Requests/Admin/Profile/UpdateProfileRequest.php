<?php

namespace App\Http\Requests\Admin\Profile;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use UsesAdminAttributes;

    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $adminId = auth('admin')->id();

        return [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email:dns|max:255|unique:admins,email,' . $adminId . ',id',
            'phone' => 'required|string|min:6|max:20|unique:admins,phone,' . $adminId . ',id',
        ];
    }
}
