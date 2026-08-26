<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;

class JoinRoomRequest extends FormRequest
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
        $room = $this->route('room');
        $isOwner = auth()->id() === $room->user_id;
        return [
            'password' => [($room->type === 'private' && !$isOwner) ? 'required' : 'nullable', 'string', 'max:255'],
            'is_muted' => 'nullable|boolean',
           
        ];
    }
}
