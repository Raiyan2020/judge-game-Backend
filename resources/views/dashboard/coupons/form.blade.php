<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('coupon details')" icon="icon-tag" col="col-lg-6">
            <x-text title="{{ __('code') }}" name="code" size="12"
                value="{{ old('code', $coupon->code ?? '') }}"></x-text>
            <x-number
                title="{{ __('discount with percentage') }}"
                name="discount"
                size="12"
                step="1"
                min="0"
                max="100"
                value="{{ old('discount', isset($coupon) ? format_discount_percent($coupon->discount, false) : '') }}"
            ></x-number>
        </x-admin-form-section>

        <x-admin-form-section :title="__('validity period')" icon="icon-calendar" col="col-lg-6">
            <x-date
                title="{{ __('start at') }}"
                name="start_at"
                size="12"
                :min="!isset($coupon) ? date('Y-m-d') : null"
                value="{{ old('start_at', isset($coupon) && $coupon->start_at ? $coupon->start_at->format('Y-m-d') : '') }}"
            ></x-date>
            <x-date
                title="{{ __('end at') }}"
                name="end_at"
                size="12"
                :min="!isset($coupon) ? date('Y-m-d') : null"
                value="{{ old('end_at', isset($coupon) && $coupon->end_at ? $coupon->end_at->format('Y-m-d') : '') }}"
            ></x-date>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
