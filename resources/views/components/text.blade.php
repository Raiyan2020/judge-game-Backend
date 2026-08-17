@props(['name', 'title', 'size' => 6, 'type' => 'text', 'hint' => null, 'pattern' => null, 'inputmode' => null])
<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $title }}"
            class="form-control {{ $extraClass ?? '' }}"
            @if ($pattern) pattern="{{ $pattern }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        >
        @if ($hint)
            <small class="form-text text-muted">{{ $hint }}</small>
        @endif
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>