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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => trans('admin.attributes.title')]),
            'title.ar.required' => __('validation.required', ['attribute' => trans('admin.attributes.title.ar')]),
            'title.en.required' => __('validation.required', ['attribute' => trans('admin.attributes.title.en')]),
            'role.required' => __('validation.required', ['attribute' => trans('admin.attributes.role')]),
            'tier.required' => __('validation.required', ['attribute' => trans('admin.attributes.tier')]),
            'tier.unique' => __('role title tier unique', ['attribute' => trans('admin.attributes.tier')]),
            'reward_points.required' => __('validation.required', ['attribute' => trans('admin.attributes.reward_points')]),
            'actions.required' => __('role title actions required'),
            'actions.min' => __('role title actions required'),
            'actions.*.role_action_id.required' => __('validation.required', ['attribute' => trans('admin.attributes.actions.*.role_action_id')]),
            'actions.*.required_count.required' => __('validation.required', ['attribute' => trans('admin.attributes.actions.*.required_count')]),
            'actions.*.required_count.max' => __('required count max exceeded', [
                'attribute' => trans('admin.attributes.actions.*.required_count'),
                'max' => number_format(RoleAction::MAX_POINTS),
            ]),
            'actions.*.required_count.integer' => __('points must be integer', [
                'attribute' => trans('admin.attributes.actions.*.required_count'),
            ]),
            'reward_points.max' => __('points max exceeded', [
                'attribute' => trans('admin.attributes.reward_points'),
                'max' => number_format(RoleAction::MAX_POINTS),
            ]),
        ];
    }
}
