<?php

namespace App\Http\Requests\Admin\Banner;

use App\Enums\BannerType;
use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required', Rule::enum(BannerType::class)],
            'title' => 'required|array',
            'title.ar' => 'required|string|min:1|max:255',
            'title.en' => 'required|string|min:1|max:255',
            'url' => 'nullable|url|max:2048',
            'image' => [request()->isMethod('post') ? 'required' : 'sometimes', 'image'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $urlField = trans('admin.attributes.url');

        return [
            'url.url' => trans('validation.url', ['attribute' => $urlField]),
            'url.max' => trans('validation.max.string', ['attribute' => $urlField, 'max' => 2048]),
        ];
    }
}
