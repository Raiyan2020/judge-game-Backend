<?php

namespace App\Http\Requests\Admin\Banner;

use App\Enum\BannerTypeEnum;
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
            'title' => 'required|array' ,
            'title.ar'=>'required|string|min:1|max:255',
            'title.en'=>'required|string|min:1|max:255',
            'url'=>'nullable|url',
            'image'=>[request()->isMethod('post') ? 'required' : 'sometimes','image'],
        ];
    }
}
