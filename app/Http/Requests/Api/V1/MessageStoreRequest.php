<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'nullable|exists:groups,id',
            'message' => 'nullable|string',
            'type' => 'nullable|string|in:text,voice,image,file',
            'file' => 'nullable', 
        ];
    }
}
