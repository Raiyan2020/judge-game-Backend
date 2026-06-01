<?php

namespace App\Http\Requests\Admin\Package;

use App\Enum\PackageAdsType;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|array' ,
            'name.ar'=>'required|string|min:1|max:255',
            'name.en'=>'required|string|min:1|max:255',
            'description' => 'required|array' ,
            'description.ar'=>'required|string|min:1|max:1000',
            'description.en'=>'required|string|min:1|max:1000',
            'price'=>'required|numeric|min:1',
            'duration_days'=>'required|numeric|min:1',
            'most_sale'=>'nullable|boolean'
        ];
    }
}
