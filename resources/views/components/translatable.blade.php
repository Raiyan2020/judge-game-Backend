    <div class="col-{{ $size ?? 6 }}">

        <div class="form-group">
            <label>{{ $title }} {{ __('in Arabic') }}</label>
            <input type="text" 
                   name="{{ $name }}[ar]" 
                   value="{{ old($name . '.ar', (isset($item) && array_key_exists('ar', $item)) ? $item['ar'] : '') }}" 
                   class="form-control" 
                   placeholder="{{ $title }} {{ __('in Arabic') }}">
            <p class="help-block"></p>
            @error($name . '.ar')
            <span style="color: red">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-{{ $size ?? 6 }}">
        <div class="form-group">
            <label>{{ $title }} {{ __('in English') }}</label>
            <input type="text" 
                   name="{{ $name }}[en]" 
                   value="{{ old($name . '.en', (isset($item) && array_key_exists('en', $item)) ? $item['en'] : '') }}" 
                   class="form-control" 
                   placeholder="{{ $title }} {{ __('in English') }}">
            <p class="help-block"></p>
            @error($name . '.en')
            <span style="color: red">{{ $message }}</span>
            @enderror
        </div>
    </div>

