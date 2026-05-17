<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
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
            'group_id' => 'required|integer|exists:groups,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:public,private',
            'password' => 'nullable|string|max:255|required_if:type,private',
            'users' => 'nullable|array',
            'users.*' => 'integer|exists:users,id',
        ];
    }
}
