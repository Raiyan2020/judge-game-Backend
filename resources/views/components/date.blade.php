@props(['name', 'title', 'size' => 12, 'value' => '', 'min' => null])
<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
         <label>{{ $title }}</label>
          <input
              type="date"
              name="{{ $name }}"
              value="{{ $value }}"
              class="form-control"
              placeholder="{{ $title }}"
              @if ($min) min="{{ $min }}" @endif
          >
        <p class="help-block"></p> 
        @error($name)
            <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>
