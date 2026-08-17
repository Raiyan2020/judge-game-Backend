@props(['name', 'title', 'size' => 12, 'value' => '', 'step' => '1', 'min' => null, 'max' => null])
<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <input
            type="number"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $title }}"
            class="form-control {{ $extraClass ?? '' }}"
            step="{{ $step }}"
            @if (! is_null($min)) min="{{ $min }}" @endif
            @if (! is_null($max)) max="{{ $max }}" @endif
        >
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>