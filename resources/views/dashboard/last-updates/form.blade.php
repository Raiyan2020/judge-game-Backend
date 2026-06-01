<div class="row">
    <x-translatable title="{{ __('title') }}" name="title" size="6" :item="isset($lastUpdate) ? $lastUpdate : null"></x-translatable>
    <x-translatable-textarea title="{{ __('description') }}" name="description" size="6"
        :item="isset($lastUpdate) ? $lastUpdate : null"></x-translatable-textarea>
    <x-number title="{{ __('version') }}" name="version" size="6"
        value="{{ old('version', $lastUpdate->version ?? '') }}"></x-number>
    <x-number title="{{ __('display speed with seconds') }}" name="display_speed" size="6"
        value="{{ old('display_speed', $lastUpdate->display_speed ?? '') }}"></x-number>
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
</div>


<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>
