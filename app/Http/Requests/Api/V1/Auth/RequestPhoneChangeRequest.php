<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Unique across the SAME country code, matching how registration
            // scopes it — the pair is the identity, not the digits alone.
            'phone' => [
                'required',
                'numeric',
                Rule::unique('users')->where(function ($query) {
                    $query->where('country_code', $this->country_code)
                        ->where('phone', $this->phone);
                }),
            ],
            'country_code' => 'required|numeric',
        ];
    }
}
