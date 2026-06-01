<div class="row">
    <x-translatable title="{{ __('name') }}" name="name" size="6" :item="isset($package) ? $package : null"></x-translatable>
    <x-number title="{{ __('price') }}" name="price" size="6"
    value="{{ old('price', $package->price ?? '') }}"></x-number>
    <x-number title="{{ __('duration in days') }}" name="duration_days" size="6"
    value="{{ old('duration_days', $package->duration_days ?? '') }}"></x-number>
     <x-translatable-textarea title="{{ __('description') }}" name="description" size="6"
        :item="isset($package) ? $package : null"></x-translatable-textarea> 
   <div class="form-group">
            <label class="text-danger font-weight-bolder">
                {{ __('most sale') }}
            </label>
            <input type="hidden" name="most_sale" value="0">

            <input type="checkbox" name="most_sale" value="1" {{ @$package->most_sale ? 'checked' : '' }}
                class="field">
            <p class="help-block"></p>
            @error('most_sale')
                <span style="color: red">{{ $message }}</span>
            @enderror
        </div>    
    
</div>

<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>


