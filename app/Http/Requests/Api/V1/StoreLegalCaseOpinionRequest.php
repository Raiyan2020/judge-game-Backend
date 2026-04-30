<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
use App\Models\LegalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLegalCaseOpinionRequest extends FormRequest
{
    protected ?LegalCase $case = null;

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
            'opinion' => [!$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'legal_arguments' => [$this->canWriteArguments() ? 'required' : 'nullable', 'array'],
            'legal_arguments.case' => [$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'legal_arguments.evidence' => [$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'legal_arguments.witnesses' => [$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'legal_arguments.damages' => [$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'legal_arguments.requests' => [$this->canWriteArguments() ? 'required' : 'nullable', 'string'],
            'final_requests' => ['nullable', 'string'],
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'videos' => 'nullable|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:10240',
            'audios' => 'nullable|array',
            'audios.*' => 'mimes:mp3,wav,m4a|max:10240',
        ];
    }

    /**
     * Check if the user can write legal arguments.
     */
    protected function canWriteArguments(): bool
    {
        $case = $this->getCase();

        if (!$case || $case->status !== LegalCaseStatus::NEW->value) {
            return false;
        }

        $participant = $case->participants()
            ->where('user_id', auth()->id())
            ->first();

        if (!$participant) {
            return false;
        }

        $allowedRoles = [
            CaseRole::DEFENDANT_LAWYER->value,
            CaseRole::CONSULTANT->value,
        ];

        return in_array($participant->role, $allowedRoles);
    }

    /**
     * Get the legal case from the request.
     */
    protected function getCase(): ?LegalCase
    {
        if ($this->case === null && $this->has('legal_case_id')) {
            $this->case = LegalCase::find($this->legal_case_id);
        }

        return $this->case;
    }
}
