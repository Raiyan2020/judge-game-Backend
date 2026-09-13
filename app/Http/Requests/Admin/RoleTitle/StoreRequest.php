<?php

namespace App\Http\Requests\Admin\RoleTitle;

use App\Enums\GroupRole;
use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Models\RoleAction;
use App\Models\RoleTitle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleTitle = $this->route('role_title');
        $roleTitleId = $roleTitle instanceof RoleTitle ? $roleTitle->getKey() : $roleTitle;

        return [
            'title' => 'required|array',
            'title.ar' => 'required|string|min:1|max:500',
            'title.en' => 'required|string|min:1|max:500',
            'role' => ['required', 'string', 'max:50', new Enum(GroupRole::class)],
            'tier' => [
                'required',
                'integer',
                'min:1',
                'max:255',
                Rule::unique('role_titles', 'tier')
                    ->where('role', $this->input('role'))
                    ->ignore($roleTitleId),
            ],
            'reward_points' => 'required|integer|min:0|max:' . RoleAction::MAX_POINTS,
            'actions' => 'required|array|min:1',
            'actions.*.role_action_id' => 'required|integer|exists:role_actions,id',
            'actions.*.required_count' => 'required|integer|min:0|max:' . RoleAction::MAX_POINTS,
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reward_points') && is_numeric($this->reward_points)) {
            $this->merge(['reward_points' => (int) $this->reward_points]);
        }

        if (! $this->has('actions')) {
            return;
        }

        $actions = collect($this->input('actions', []))->map(function ($action) {
            if (array_key_exists('required_count', $action) && is_numeric($action['required_count'])) {
                $action['required_count'] = (int) $action['required_count'];
            }

            return $action;
        })->all();

        $this->merge(['actions' => $actions]);
    }

    /**
     * Only rules whose sentence is NOT covered by lang/{locale}/validation.php are
     * overridden here. Everything else (required / integer / array / string / min /
     * max / enum / exists) deliberately falls through to the localized validation
     * file so the Arabic wording stays in one place.
     *
     * IMPORTANT — do not re-introduce `['attribute' => trans('admin.attributes.<x>')]`
     * here. `lang/{locale}/admin.php` stores the per-field names as FLAT keys that
     * themselves contain dots ('title.ar', 'actions.*.required_count', ...). The
     * translator resolves a key with `Arr::get()`, which walks the dots segment by
     * segment: 'attributes' -> array, 'title' -> the STRING 'العنوان', then it cannot
     * descend into a string and returns null, so `trans()` hands back the raw key.
     * That is exactly what shipped before and why the messages rendered with a bare
     * `admin.attributes.title.ar` spliced into the Arabic sentence (bug B-08).
     *
     * `:attribute` is intentionally left untouched in every message below: the
     * validator substitutes it afterwards from `attributes()` (UsesAdminAttributes),
     * which looks the flat dotted keys up by exact array key and therefore DOES
     * resolve them.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tier.unique' => __('role title tier unique'),
            'actions.required' => __('role title actions required'),
            'actions.min' => __('role title actions required'),
            'actions.*.required_count.integer' => __('points must be integer'),
            'actions.*.required_count.max' => __('required count max exceeded', [
                'max' => number_format(RoleAction::MAX_POINTS),
            ]),
            'reward_points.max' => __('points max exceeded', [
                'max' => number_format(RoleAction::MAX_POINTS),
            ]),
        ];
    }
}
