<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
         <label>{{ $title }}</label>
          <input type="time" name="{{ $name }}" value="{{ $value }}" class="form-control" placeholder="{{ $title }}">
        <p class="help-block"></p> 
        @error($name)
            <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>
