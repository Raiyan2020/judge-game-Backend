@if ($roleTitle->requirements->count())
    <div class="admin-show-items">
        @foreach ($roleTitle->requirements as $requirement)
            <div class="admin-show-item">
                <div class="admin-show-item__main">
                    <div class="admin-show-item__info">
                        <div class="admin-show-item__name">{{ $requirement->action->title ?? '-' }}</div>
                    </div>
                </div>
                <div class="admin-show-item__meta">
                    <div class="admin-show-item__stat">
                        <span class="admin-show-item__stat-label">{{ __('Required Count') }}</span>
                        <span class="admin-show-item__stat-value">{{ $requirement->required_count }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <span class="admin-show-empty">{{ __('There is no data') }}</span>
@endif
