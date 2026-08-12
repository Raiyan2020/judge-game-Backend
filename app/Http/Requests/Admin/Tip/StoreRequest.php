<?php

namespace App\Http\Requests\Admin\Tip;

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
            'description' => 'required|array',
            'description.ar'=>'required|string|min:1|max:255',
            'description.en'=>'required|string|min:1|max:255',
            'image'=>[request()->isMethod('post') ? 'required' : 'sometimes','image'],
        ];
    }
}
