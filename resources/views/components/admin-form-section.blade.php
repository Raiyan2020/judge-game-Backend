<div class="{{ $col ?? 'col-12' }} mb-2">
    <div class="card admin-form-card h-100 mb-0">
        <div class="card-header admin-form-card__header">
            <h4 class="card-title admin-form-card__title mb-0">
                @if (!empty($icon))
                    <i class="feather {{ $icon }}"></i>
                @endif
                <span>{{ $title }}</span>
            </h4>
        </div>
        <div class="card-content">
            <div class="card-body admin-form-card__body">
                <div class="row">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
