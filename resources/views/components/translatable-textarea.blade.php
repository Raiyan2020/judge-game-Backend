<div class="col-{{ $size ?? 12 }}">

        <div class="form-group">
            <label>{{ $title }} {{ __('in Arabic') }}</label>
            <textarea name="{{ $name }}[ar]" class="form-control {{ $extraClass ?? '' }} " placeholder="{{ $title }} {{ __('in Arabic') }}">{{ old($name . '.ar', isset($item) && array_key_exists('ar', $item) ? $item['ar'] : '') }}</textarea>
            <p class="help-block"></p>
            @error($name . '.ar')
                <span style="color: red">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-{{ $size ?? 12 }}">

        <div class="form-group">
            <label>{{ $title }} {{ __('in English') }}</label>
            <textarea name="{{ $name }}[en]" class="form-control {{ $extraClass ?? '' }}"
                placeholder="{{ $title }} {{ __('in English') }}">{{ old($name . '.en', isset($item) && array_key_exists('en', $item) ? $item['en'] : '') }}</textarea>
            <p class="help-block"></p>
            @error($name . '.en')
                <span style="color: red">{{ $message }}</span>
            @enderror
        </div>
    </div>

