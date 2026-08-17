<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('content')" icon="icon-file-text" col="col-lg-7">
            <x-translatable-textarea title="{{ __('description') }}" name="description" size="12"
                :item="isset($tip) ? $tip : null"></x-translatable-textarea>
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-lg-5">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200" accept="image/jpeg,image/png,image/gif,image/webp"
                        {{ @$tip->image ? 'data-default-file=' . $tip->image . '' : '' }}>
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
