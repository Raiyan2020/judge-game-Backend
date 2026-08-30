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
            'damages' => 'required|string',
            'participants' => 'required|array|min:1',
            'participants.*.user_id' => 'required|integer|exists:users,id',
            'participants.*.role' => 'required|string|in:defendant,witness,plaintiff_lawyer',
            'images' => 'nullable|array',
            'images.*' => 'image|max:15360',
            'videos' => 'nullable|array',
            // Validate by real MIME, not extension: a camera video (iOS
            // video/quicktime .mov, Android video/mp4 / 3gpp) failed `mimes:`
            // extension-guessing. Uniform 15MB cap across all evidence types.
            // (JG-030; also raise php.ini upload_max_filesize/post_max_size.)
            'videos.*' => 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/3gpp,video/x-matroska|max:15360',
            'audios' => 'nullable|array',
            'audios.*' => 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/aac|max:15360',
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

            // A party can't also be the opposing side's lawyer: reject when the
            // same user_id appears as BOTH the defendant and the plaintiff lawyer.
            $defendantIds = collect($participants)
                ->where('role', 'defendant')
                ->pluck('user_id');

            $plaintiffLawyerIds = collect($participants)
                ->where('role', 'plaintiff_lawyer')
                ->pluck('user_id');

            if ($defendantIds->intersect($plaintiffLawyerIds)->isNotEmpty()) {
                $validator->errors()->add('participants', __('The same person cannot be both a defendant and the plaintiff lawyer'));
            }
        });
    }
}
