<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AcceptLegalCaseJudgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_case_id' => ['required', 'exists:legal_cases,id'],
            ];
    }
}
