<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\LegalCaseJudgmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLegalCaseJudgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_case_id' => ['required', 'exists:legal_cases,id'],
            'judgment_type' => ['required', new Enum(LegalCaseJudgmentType::class)],
            'judgment_text' => ['required', 'string'],
        ];
    }
}
