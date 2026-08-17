<?php

namespace App\Http\Requests\Admin\Tip;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Http\Requests\Admin\Concerns\ValidatesAdminImageUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;
    use ValidatesAdminImageUpload;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'required|array',
            'description.ar' => 'required|string|min:1|max:255',
            'description.en' => 'required|string|min:1|max:255',
            'image' => $this->adminImageRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->adminImageMessages();
    }
}
