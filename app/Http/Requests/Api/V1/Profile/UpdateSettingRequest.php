<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Whitelisted to the locales the app actually ships. `string|max:10`
            // accepted anything, so `language=zz` persisted onto the profile.
            'language' => 'sometimes|string|in:ar,en',
            'notified' => 'sometimes|boolean',
        ];
    }
}
