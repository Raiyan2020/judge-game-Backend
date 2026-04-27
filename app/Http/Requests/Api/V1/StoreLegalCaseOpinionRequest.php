<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CaseRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLegalCaseOpinionRequest extends FormRequest
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
            'legal_case_id' => ['required', 'exists:legal_cases,id'],
            'opinion' => ['required', 'string'],
            'final_requests' => ['nullable', 'string'],
            'role' => ['nullable', 'string', new Enum(CaseRole::class)],
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'videos' => 'nullable|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:10240',
            'audios' => 'nullable|array',
            'audios.*' => 'mimes:mp3,wav,m4a|max:10240',
        ];
    }
}
