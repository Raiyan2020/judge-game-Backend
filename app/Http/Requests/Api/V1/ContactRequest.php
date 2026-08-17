<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country_code' => 'nullable|string|max:5',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country_code') && $this->country_code !== null) {
            $this->merge([
                'country_code' => ltrim((string) $this->country_code, '+'),
            ]);
        }
    }
}