<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <input type="number" name="{{ $name }}" value="{{$value}}" placeholder="{{ $title }}" class="form-control {{ $extraClass ?? '' }}" step="{{ $step}}" min="0">
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>