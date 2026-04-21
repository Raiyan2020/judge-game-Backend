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
            'group_law_id' => 'required|integer|exists:group_laws,id',
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
}
