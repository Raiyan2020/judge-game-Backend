<?php

namespace App\Http\Requests\Admin\Banner;

use App\Enums\BannerType;
use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Http\Requests\Admin\Concerns\ValidatesAdminImageUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;
    use ValidatesAdminImageUpload;

    /**
     * Maximum accepted banner image weight, in kilobytes.
     * Kept in sync with the ":max ميجابايت" wording of adminImageMessages().
     */
    public const MAX_IMAGE_KILOBYTES = 2048;

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
            'image' => $this->imageRules(),
        ];
    }

    /**
     * The image is mandatory when a banner is created and optional when it is
     * edited. "nullable" (not "sometimes") is used on update because an
     * untouched file input is still submitted - as null - so the remaining
     * rules would otherwise reject a plain "edit the title" save.
     *
     * @return array<int, string>
     */
    protected function imageRules(): array
    {
        return [
            $this->isMethod('post') ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpeg,jpg,png,gif,webp',
            'max:' . self::MAX_IMAGE_KILOBYTES,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $urlField = trans('admin.attributes.url');

        return array_merge($this->adminImageMessages(), [
            'url.url' => trans('validation.url', ['attribute' => $urlField]),
            'url.max' => trans('validation.max.string', ['attribute' => $urlField, 'max' => 2048]),
        ]);
    }
}
