<div class="card admin-show-section h-100 mb-0">
    <div class="card-header admin-show-section__header">
        <h4 class="card-title mb-0">{{ $title ?? '' }}</h4>
        <div class="admin-show-section__header-icon">
            <i class="feather icon-layers"></i>
        </div>
    </div>
    <div class="card-content">
        @if (!empty($content))
            <div class="card-body admin-show-section__body">
                {!! $content !!}
            </div>
        @else
            <div class="card-body admin-show-section__body">
                <div class="admin-show-fields">
                    @foreach (($rows ?? []) as $row)
                        @php
                            $value = $row['value'] ?? '-';
                            $isFull = ($row['full'] ?? false)
                                || strlen(strip_tags((string) $value)) > 100
                                || str_contains((string) $value, '<br')
                                || str_contains((string) $value, '<img');
                        @endphp
                        <div class="admin-show-field{{ $isFull ? ' admin-show-field--full' : '' }}">
                            <span class="admin-show-field__label">{{ $row['label'] }}</span>
                            <div class="admin-show-field__value">{!! $value !!}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
