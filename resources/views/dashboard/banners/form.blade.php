<div class="row">
    <x-translatable title="{{ __('title') }}" name="title" size="6" :item="isset($banner) ? $banner : null"></x-translatable>
    <x-text title="{{ __('url') }}" name="url" size="12" value="{{ old('url', $banner->url ?? '') }}"></x-text>
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
</div>



<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">حفظ</button>
