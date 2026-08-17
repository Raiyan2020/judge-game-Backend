<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-package" col="col-lg-6">
            <x-translatable title="{{ __('name') }}" name="name" size="12" :item="isset($package) ? $package : null"></x-translatable>
            <div class="col-12">
                <div class="form-group">
                    <label class="text-danger font-weight-bolder">
                        {{ __('most sale') }}
                    </label>
                    <input type="hidden" name="most_sale" value="0">
                    <input type="checkbox" name="most_sale" value="1" {{ @$package->most_sale ? 'checked' : '' }} class="field">
                    @error('most_sale')
                        <span style="color: red">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-admin-form-section>

        <x-admin-form-section :title="__('pricing details')" icon="icon-dollar-sign" col="col-lg-6">
            <x-number
                title="{{ __('price') }}"
                name="price"
                size="12"
                step="1"
                min="1"
                value="{{ old('price', isset($package) ? format_package_price($package->price) : '') }}"
            ></x-number>
            <x-number title="{{ __('duration in days') }}" name="duration_days" size="12"
                value="{{ old('duration_days', $package->duration_days ?? '') }}"></x-number>
        </x-admin-form-section>

        <x-admin-form-section :title="__('description')" icon="icon-file-text" col="col-12">
            <x-translatable-textarea title="{{ __('description') }}" name="description" size="12"
                :item="isset($package) ? $package : null"></x-translatable-textarea>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
