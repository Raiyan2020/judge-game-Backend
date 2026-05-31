<div class="row">
    <x-translatable-textarea title="{{ __('description') }}" name="description" size="6"
        :item="isset($tip) ? $tip : null"></x-translatable-textarea> 
           <div class="col-12">
        <div class="form-group">
            <label>{{ __('image') }}</label>
            <input type="file" name="image" class="dropify" data-height="200" accept="image/*"
                {{ @$tip->image ? 'data-default-file=' . $tip->image . '' : '' }}>
            @error('image')
                <span style="color: red">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>


<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>
