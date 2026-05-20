<?php

namespace App\Http\Requests\Admin\User;

use App\Enum\ProfileType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', Rule::unique('users')->where(function ($query) {
                $query->where('country_code', $this->country_code)->where('phone', $this->phone);
            })->ignore($userId)],
            'whatsapp' => ['nullable', Rule::unique('users')->where(function ($query) {
                $query->where('whatsapp_country_code', $this->whatsapp_country_code)->where('whatsapp', $this->whatsapp);
            })->ignore($userId)],
            'country_code' => ['required', Rule::exists('countries', 'country_code')],
            'whatsapp_country_code' => ['nullable', Rule::exists('countries', 'country_code')],
            'profile_type' => ['required',new Enum(ProfileType::class)],
            'image' => ['sometimes', 'image'],
          
        ];
    }
}
