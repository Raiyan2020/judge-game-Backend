<?php

namespace App\Http\Requests\Admin\Concerns;

trait ValidatesAdminImageUpload
{
    /**
     * @return array<int, string>
     */
    protected function adminImageRules(bool $requiredOnCreate = true): array
    {
        $presence = request()->isMethod('post') && $requiredOnCreate ? 'required' : 'sometimes';

        return [
            $presence,
            'file',
            'image',
            'mimes:jpeg,jpg,png,gif,webp',
            'max:2048',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function adminImageMessages(): array
    {
        $field = trans('admin.attributes.image');

        return [
            'image.required' => __('admin image required', ['attribute' => $field]),
            'image.image' => __('admin image invalid format', ['attribute' => $field]),
            'image.mimes' => __('admin image mimes', ['attribute' => $field]),
            'image.max' => __('admin image max size', ['attribute' => $field, 'max' => 2]),
            'image.uploaded' => __('admin image upload failed', ['attribute' => $field]),
            'image.file' => __('admin image invalid format', ['attribute' => $field]),
        ];
    }
}
