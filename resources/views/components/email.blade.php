<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <input type="email" name="{{ $name }}" value="{{$value}}" placeholder="{{ $title }}" class="form-control" >
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>
