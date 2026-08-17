@php
    $startIndex = $startIndex ?? 0;
    $sectionTitle = $sectionTitle ?? null;
    $sectionHint = $sectionHint ?? null;
    $hideHeader = $hideHeader ?? false;
@endphp

@if ($sectionTitle)
    <h5 class="mb-1 mt-1">{{ $sectionTitle }}</h5>
    @if ($sectionHint)
        <p class="text-muted small mb-1">{{ $sectionHint }}</p>
    @endif
@endif

<div class="table-responsive mb-2">
    <table class="table table-bordered table-striped mb-0">
        @unless ($hideHeader)
            <thead>
                <tr>
                    <th width="60%">{{ __('action') }}</th>
                    <th width="25%">{{ __('points') }}</th>
                </tr>
            </thead>
        @endunless
        <tbody>
            @foreach ($actions as $offset => $action)
                @php($index = $startIndex + $offset)
                <tr>
                    <td>
                        {{ $action->localizedTitle() }}
                        <input type="hidden" name="actions[{{ $index }}][id]" value="{{ $action->id }}">
                    </td>
                    <td>
                        <input type="number"
                            class="form-control"
                            name="actions[{{ $index }}][points]"
                            value="{{ old("actions.$index.points", (int) $action->points) }}"
                            min="0"
                            max="{{ \App\Models\RoleAction::MAX_POINTS }}"
                            step="1">
                        @error("actions.$index.points")
                            <span style="color: red">{{ $message }}</span>
                        @enderror
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
