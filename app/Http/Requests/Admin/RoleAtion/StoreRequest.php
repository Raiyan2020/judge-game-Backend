<?php

namespace App\Http\Requests\Admin\RoleAtion;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Models\RoleAction;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'actions' => 'required|array|min:1',
            'role' => 'nullable|string|max:50',
            'actions.*.id' => 'required|integer|exists:role_actions,id',
            'actions.*.points' => 'required|integer|min:0|max:' . RoleAction::MAX_POINTS,
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('actions')) {
            return;
        }

        $actions = collect($this->input('actions', []))->map(function ($action) {
            if (array_key_exists('points', $action) && is_numeric($action['points'])) {
                $action['points'] = (int) $action['points'];
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
        $field = trans('admin.attributes.actions.*.points');

        return [
            'actions.*.points.max' => __('points max exceeded', [
                'attribute' => $field,
                'max' => number_format(RoleAction::MAX_POINTS),
            ]),
            'actions.*.points.integer' => __('points must be integer', ['attribute' => $field]),
        ];
    }
}
