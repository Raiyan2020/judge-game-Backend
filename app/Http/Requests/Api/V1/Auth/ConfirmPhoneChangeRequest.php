<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Same shape as the login code (`CheckCodeRequest`). The number
            // itself is not resent: it is whatever the request step staged, so
            // a caller cannot confirm a different number than the one verified.
            'code' => ['required', 'digits:4'],
        ];
    }
}
