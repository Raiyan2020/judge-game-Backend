<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
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
            // Unique PER OWNER: a user may not create two groups with the same
            // name (the app used to allow "الديوانية" twice). Global uniqueness
            // would wrongly collide across unrelated users.
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups', 'name')->where(
                    fn ($query) => $query->where('user_id', auth()->id())
                ),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
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