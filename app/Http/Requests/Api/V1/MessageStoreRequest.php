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
            'receiver_id' => 'requiredIf:group_id,null|exists:users,id',
            'group_id' => 'requiredIf:receiver_id,null|exists:groups,id',
            'message' => 'nullable|string',
            'type' => 'nullable|string|in:text,voice,image,file',
            'attachment' => 'nullable', 
        ];
    }
}
