<div class="col-{{ $size ?? 12 }}">
    <div class="form-group">
        <label for="{{ $name }}">{{ $title }}</label>
        @php
            $selectedValues = old(str_replace('[]', '', $name), $selected ?? []);
            $selectedValues = is_array($selectedValues) ? $selectedValues : [$selectedValues];
          
        @endphp
        <select name="{{ $name }}{{ $multiple ? '[]' : '' }}" class="form-control {{ $extraClass ?? '' }} select2{{ $multiple ? ' multiple' : '' }} " {{ $multiple ? 'multiple' : '' }}>
            @foreach($items as $key => $value)
                <option value="{{ $key }}" {{ in_array($key, $selectedValues) ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
        </select>
        <p class="help-block"></p>
        @error(str_replace('[]', '', $name))
        <span style="color: red">{{ $message }}</span>
        @enderror
    </div>
</div>
