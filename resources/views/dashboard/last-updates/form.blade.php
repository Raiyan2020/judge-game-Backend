<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-info" col="col-lg-6">
            <x-translatable title="{{ __('title') }}" name="title" size="12" :item="isset($lastUpdate) ? $lastUpdate : null"></x-translatable>
            <x-number title="{{ __('version') }}" name="version" size="6"
                value="{{ old('version', $lastUpdate->version ?? '') }}"></x-number>
            <x-number title="{{ __('display speed with seconds') }}" name="display_speed" size="6"
                value="{{ old('display_speed', $lastUpdate->display_speed ?? '') }}"></x-number>
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-lg-6">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200" accept="image/*"
                        {{ @$lastUpdate->image ? 'data-default-file=' . $lastUpdate->image . '' : '' }}>
                    @error('image')
                        <span style="color: red">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-admin-form-section>

        <x-admin-form-section :title="__('description')" icon="icon-file-text" col="col-12">
            <x-translatable-textarea title="{{ __('description') }}" name="description" size="12"
                :item="isset($lastUpdate) ? $lastUpdate : null"></x-translatable-textarea>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
