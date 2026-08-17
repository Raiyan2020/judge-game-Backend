<?php

namespace App\Http\Requests\Admin\Profile;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
        return [
            'current_password' => 'required|current_password:admin',
            'password' => 'required|string|min:6|max:255|confirmed',
        ];
    }
}
