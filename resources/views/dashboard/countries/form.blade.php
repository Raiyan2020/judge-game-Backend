<div class="row">


<x-translatable title="{{ __('name') }}" name="name"  :item="isset($country) ? $country : null"></x-translatable>
<x-text title="{{ __('phone code') }}" name="country_code" size="12" value="{{ old('country_code', $country->country_code ?? '') }}"></x-text>
<div class="col-12">
    <div class="form-group">
        <label>{{ __('image') }}</label>
        <input type="file" name="image" class="dropify" data-height="200" accept="image/*" {{ @$country->image ? 'data-default-file=' . $country->image . '' : '' }}>
        @error('image')
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>

<button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>
</div>