<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-type" col="col-lg-6">
            <x-select
                name="type"
                :items="\App\Enums\BannerType::options()"
                title="{{ __('banner type') }}"
                size="12"
                :selected="[isset($banner) ? $banner->type?->value : request('type', \App\Enums\BannerType::HOME->value)]"
            />
            <x-translatable title="{{ __('title') }}" name="title" size="12" :item="isset($banner) ? $banner : null"></x-translatable>
            <x-text
                title="{{ __('link url') }}"
                name="url"
                type="url"
                size="12"
                :hint="__('banner link url hint')"
                value="{{ old('url', $banner->url ?? '') }}"
            ></x-text>
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-lg-6">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200" accept="image/*"
                        {{ @$banner->image ? 'data-default-file=' . $banner->image . '' : '' }}>
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
