<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LegalCaseRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_id' => 'required|integer|exists:groups,id',
            'group_law_ids' => 'required|array|min:1',
            'group_law_ids.*' => 'integer|exists:group_laws,id',
            'point_value' => 'nullable|integer',
            'participants' => 'required|array|min:1',
            'participants.*.user_id' => 'required|integer|exists:users,id',
            'participants.*.role' => 'required|string|in:defendant,witness,plaintiff_lawyer',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'videos' => 'nullable|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:10240',
            'audios' => 'nullable|array',
            'audios.*' => 'mimes:mp3,wav,m4a|max:10240',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $participants = $this->participants ?? [];

            $roles = collect($participants)->pluck('role');

            if (!$roles->contains('defendant')) {
                $validator->errors()->add('participants', __('At least one defendant is required in the group to create a legal case'));
            }

            if (!$roles->contains('plaintiff_lawyer')) {
                $validator->errors()->add('participants', __('At least one plaintiff lawyer is required in the group to create a legal case'));
            }
        });
    }
}
