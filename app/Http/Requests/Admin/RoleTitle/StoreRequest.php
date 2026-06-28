<?php

namespace App\Http\Requests\Admin\RoleTitle;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */

    public function rules(): array
    {
        return [
             'title' => 'required|array' ,
            'title.ar' => ['required'],
            'title.en' => ['required'],

            'role' => ['required'],

            'actions' => ['required', 'array'],

            'actions.*.role_action_id' => [
                'required',
                'exists:role_actions,id'
            ],

            'actions.*.required_count' => [
                'required',
                'integer',
                'min:1'
            ],
        ];
    }
}
