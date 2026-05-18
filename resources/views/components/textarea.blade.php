  <div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <textarea name="{{ $name }}" placeholder="{{ $title }}" class="form-control {{ $extraClass ?? '' }}">{{ old($name, $value ?? ($item[$name] ?? '')) }}
</textarea>
        <p class="help-block"></p>
        @error($name)
            <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>

