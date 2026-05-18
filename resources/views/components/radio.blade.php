<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <div>
            @foreach($options as $key => $option)
                <div class="form-check">
                    <input class="form-check-input {{ $extraClass ?? '' }}" type="radio" name="{{ $name }}" id="{{ $name }}-{{ $key }}" value="{{ $key }}" {{ $value == $key ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $name }}-{{ $key }}">
                        {{ $option }}
                    </label>
                </div>
            @endforeach
        </div>
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>