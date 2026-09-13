<?php

namespace App\Http\Requests\Admin\User;

use App\Enums\UserStatus;
use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Http\Requests\Admin\Concerns\ValidatesAdminImageUpload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the dashboard "add / edit user" form.
 *
 * Every rule here maps to a REAL column on `users`
 * (`0001_01_01_000000_create_users_table` + `add_country_id_to_users`).
 *
 * The page's actual blocker was in `UserController` (undefined `UserService`
 * methods — see that class). This file held the LATENT failures waiting behind
 * it: it validated `profile_type`, `whatsapp` and `whatsapp_country_code`, none
 * of which exist on this project's `users` table, and it imported a
 * non-existent `App\Enum\ProfileType` so that required rule could never pass;
 * meanwhile it never validated `username`, which is NOT NULL + UNIQUE, so the
 * next failure after the controller was fixed would have been
 * `SQLSTATE[HY000] 1364 Field 'username' doesn't have a default value`.
 *
 * `full_phone` is a STORED generated column (`CONCAT(country_code, phone)`) and
 * is therefore never validated nor written.
 */
class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;
    use ValidatesAdminImageUpload;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The user being edited, or null when creating.
     *
     * Read from the ROUTE, never from `$this->user`: `Request::__get()` looks at
     * the request INPUT first and only then falls back to the route, so a posted
     * field named `user` would shadow the bound model, null the unique-rule
     * exclusion, and make "save an unchanged user" fail as a duplicate.
     */
    private function editedUserId(): ?int
    {
        $user = $this->route('user');

        return $user instanceof User ? $user->id : null;
    }

    /**
     * Normalise the identity fields before validation so that stray spaces
     * cannot smuggle a duplicate phone past the unique rule.
     *
     * The phone code is only trimmed — never stripped of its "+" — because the
     * select is populated from `countries.country_code` and must match the
     * stored value exactly for `Rule::exists()` to pass.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('name'))) {
            $data['name'] = trim($this->input('name'));
        }

        if (is_string($this->input('username'))) {
            $data['username'] = trim($this->input('username'));
        }

        if (is_string($this->input('nickname'))) {
            $data['nickname'] = trim($this->input('nickname'));
        }

        if (is_string($this->input('country_code'))) {
            $data['country_code'] = trim($this->input('country_code'));
        }

        if (is_string($this->input('phone'))) {
            $data['phone'] = preg_replace('/\s+/', '', trim($this->input('phone')));
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->editedUserId();

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],

            'nickname' => ['nullable', 'string', 'max:255'],

            // Bounded to digits so an oversized / non-numeric entry is rejected
            // with a localized message instead of reaching the database.
            'country_code' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{1,5}$/',
                Rule::exists('countries', 'country_code'),
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]{6,20}$/',
                Rule::unique('users', 'phone')
                    ->where(fn ($query) => $query->where('country_code', $this->input('country_code')))
                    ->ignore($userId),
            ],

            'gender' => ['nullable', 'in:male,female'],

            // The lower bound keeps an out-of-range date (e.g. year 0) from
            // becoming an "Incorrect date value" database exception.
            'birthdate' => ['nullable', 'date', 'after:1900-01-01', 'before_or_equal:today'],

            'language' => ['required', 'string', 'in:ar,en'],

            'status' => ['required', 'string', Rule::in(array_column(UserStatus::cases(), 'value'))],

            // `integer` rejects a number too large to be an int before it can
            // overflow the column; `exists` then confirms the country is real.
            'country_id' => [
                'nullable',
                'integer',
                'min:1',
                'max:4294967295',
                Rule::exists('countries', 'id'),
            ],

            // The avatar is optional on BOTH create and edit, so `nullable`
            // fronts the shared rules: an untouched file input arrives as null
            // and must skip validation rather than fail as "not a file".
            'image' => array_merge(['nullable'], $this->adminImageRules(false)),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->adminImageMessages(), [
            'country_code.regex' => __('admin invalid phone code'),
            'phone.regex' => __('admin invalid phone number'),
            'country_id.integer' => __('admin invalid number'),
            'country_id.min' => __('admin invalid number'),
            'country_id.max' => __('admin invalid number'),
        ]);
    }
}
