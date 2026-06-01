<?php

namespace App\Http\Requests\Admin\LastUpdate;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|array',
            'title.ar'=>'required|string|min:1|max:255',
            'title.en'=>'required|string|min:1|max:255',
            'description' => 'required|array',
            'description.ar'=>'required|string|min:1|max:255',
            'description.en'=>'required|string|min:1|max:255',
            'version' => 'required|numeric|min:0',
            'display_speed' => 'required|numeric|min:0',
            'image'=>[request()->isMethod('post') ? 'required' : 'sometimes','image'],
        ];
    }
}
