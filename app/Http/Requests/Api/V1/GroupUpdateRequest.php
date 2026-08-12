<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupUpdateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Same per-owner uniqueness as create, but ignoring THIS group so a
            // no-op save (or an image-only edit) doesn't collide with itself.
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('groups', 'name')
                    ->where(fn ($query) => $query->where('user_id', auth()->id()))
                    ->ignore($this->route('group')),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('You already have a group with this name'),
        ];
    }
}