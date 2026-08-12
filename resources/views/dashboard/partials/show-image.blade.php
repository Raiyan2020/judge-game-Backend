@if (!empty($url))
    <div class="admin-show-avatar">
        <img src="{{ $url }}" alt="">
    </div>
@else
    <span class="admin-show-empty">{{ __('no image') }}</span>
@endif
