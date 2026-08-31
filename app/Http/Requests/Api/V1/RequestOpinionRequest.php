<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestOpinionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The judge-only authorization is enforced in the service (owner /
        // judge participant), where the case is loaded — not here.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The app sends `defense_lawyer`; `defendant_lawyer` (the stored
            // participant role) is accepted too so either naming resolves. The
            // service maps both to the defendant-lawyer participant row.
            'role' => ['required', Rule::in(['defense_lawyer', 'defendant_lawyer', 'consultant'])],
        ];
    }
}
