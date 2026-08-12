<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('coupon details')" icon="icon-tag" col="col-lg-6">
            <x-text title="{{ __('code') }}" name="code" size="12"
                value="{{ old('code', $coupon->code ?? '') }}"></x-text>
            <x-number title="{{ __('discount with percentage') }}" name="discount" size="12"
                value="{{ old('discount', $coupon->discount ?? '') }}"></x-number>
        </x-admin-form-section>

        <x-admin-form-section :title="__('validity period')" icon="icon-calendar" col="col-lg-6">
            <x-date title="{{ __('start at') }}" name="start_at" size="12"
                value="{{ old('start_at', $coupon->start_at ?? '') }}"></x-date>
            <x-date title="{{ __('end at') }}" name="end_at" size="12"
                value="{{ old('end_at', $coupon->end_at ?? '') }}"></x-date>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
