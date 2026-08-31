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
            'images.*' => 'image|max:15360',
            'videos' => 'nullable|array',
            // Real-MIME validation so camera clips (quicktime/3gpp) pass;
            // uniform 15MB cap across all evidence types (JG-030).
            'videos.*' => 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/3gpp,video/x-matroska|max:15360',
            'audios' => 'nullable|array',
            'audios.*' => 'mimes:mp3,wav,m4a|max:15360',
        ];
    }

    /**
     * Check if the user can write legal arguments.
     */
    protected function canWriteArguments(): bool
    {
        $case = $this->getCase();

        // Structured 5-field arguments are required at first instance, pre-ruling
        // — `new` OR `in_progress` (a hearing scheduled does not change the
        // filing shape). Any later stage falls back to free-text `opinion`.
        if (!$case || !in_array(
            $case->status,
            [LegalCaseStatus::NEW->value, LegalCaseStatus::IN_PROGRESS->value],
            true
        )) {
            return false;
        }

        // Prefer the lawyer role for a self-representing defendant so they are
        // required to write the structured 5-field argument (not downgraded to
        // free-text) — their `defendant` row alone would fail this gate.
        $role = $case->participantRoleFor(auth()->id());

        if ($role === null) {
            return false;
        }

        $allowedRoles = [
            CaseRole::DEFENDANT_LAWYER->value,
            CaseRole::CONSULTANT->value,
        ];

        return in_array($role, $allowedRoles, true);
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
