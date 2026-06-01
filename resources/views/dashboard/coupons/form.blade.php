<div class="row">
    <x-text title="{{ __('code') }}" name="code" size="6"
        value="{{ old('code', $coupon->code ?? '') }}"></x-text>
    <x-number title="{{ __('discount with percentage') }}" name="discount" size="6"
        value="{{ old('discount', $coupon->discount ?? '') }}"></x-number>
    <x-date title="{{ __('start at') }}" name="start_at" size="6"
      value="{{ old('start_at', $coupon->start_at ?? '') }}"></x-date>

    <x-date title="{{ __('end at') }}" name="end_at" size="6"
      value="{{ old('end_at', $coupon->end_at ?? '') }}"></x-date>

</div>

<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>
