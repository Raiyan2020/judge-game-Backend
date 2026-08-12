<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\Concerns\UsesAdminAttributes;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use UsesAdminAttributes;
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
            'code' => [
                'required',
                'string',
                'min:1',
                'max:14',
                request()->isMethod('post')
                    ? 'unique:coupons,code'
                    : 'unique:coupons,code,' . $this->route('coupon')->id . ',id',
            ],
            'discount' => 'required|numeric|min:0|max:100',
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ];
    }
}
