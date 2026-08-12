<?php

namespace App\Http\Requests\Admin\Country;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|array' ,
            'name.ar'=>'required|string|min:1|max:255',
            'name.en'=>'required|string|min:1|max:255',
            'country_code' => 'required|string|min:1|max:5',
            'image'=>[request()->isMethod('post') ? 'required' : 'sometimes','image'],
        ];
    }
}
