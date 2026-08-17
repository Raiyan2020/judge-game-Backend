<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $coupon = $this->route('coupon');
        $couponId = $coupon instanceof Coupon ? $coupon->getKey() : $coupon;

        $startAtRules = ['required', 'date'];
        $endAtRules = ['required', 'date', 'after_or_equal:start_at'];

        if ($this->isMethod('post')) {
            $startAtRules[] = 'after_or_equal:today';
            $endAtRules[] = 'after_or_equal:today';
        }

        return [
            'code' => [
                'required',
                'string',
                'min:1',
                'max:14',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'discount' => 'required|integer|min:0|max:100',
            'start_at' => $startAtRules,
            'end_at' => $endAtRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $startAt = trans('admin.attributes.start_at');
        $endAt = trans('admin.attributes.end_at');

        return [
            'start_at.after_or_equal' => __('coupon start date invalid', ['attribute' => $startAt]),
            'end_at.after_or_equal' => __('coupon end date invalid', ['attribute' => $endAt]),
            'end_at.after' => __('coupon end date before start', ['attribute' => $endAt]),
        ];
    }
}
