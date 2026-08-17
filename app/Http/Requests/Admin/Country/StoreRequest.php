<?php

namespace App\Http\Requests\Admin\Country;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Http\Requests\Admin\Concerns\ValidatesAdminImageUpload;
use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $country = $this->route('country');
        $countryId = $country instanceof Country ? $country->getKey() : $country;

        return [
            'name' => 'required|array',
            'name.ar' => 'required|string|min:1|max:255',
            'name.en' => 'required|string|min:1|max:255',
            'country_code' => [
                'required',
                'string',
                'regex:/^\d{1,4}$/',
                Rule::unique('countries', 'country_code')->ignore($countryId),
            ],
            'image' => $this->adminImageRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country_code')) {
            $this->merge([
                'country_code' => ltrim((string) $this->country_code, '+'),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $field = trans('admin.attributes.country_code');

        return array_merge($this->adminImageMessages(), [
            'country_code.regex' => __('country code format invalid', ['attribute' => $field]),
            'country_code.unique' => __('country code already exists', ['attribute' => $field]),
        ]);
    }
}
