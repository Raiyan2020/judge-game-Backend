<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-map-pin" col="col-lg-6">
            <x-translatable title="{{ __('name') }}" name="name" size="12" :item="isset($country) ? $country : null"></x-translatable>
            <x-text title="{{ __('phone code') }}" name="country_code" size="12" value="{{ old('country_code', $country->country_code ?? '') }}"></x-text>
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-lg-6">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200" accept="image/*"
                        {{ @$country->image ? 'data-default-file=' . $country->image . '' : '' }}>
                    @error('image')
                        <span style="color: red">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
