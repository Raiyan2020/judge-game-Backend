<div class="content-body admin-show-page">
    <section id="basic-input">
        <div class="admin-show-page__hero">
            <div class="admin-show-page__hero-main">
                @if (!empty($backUrl))
                    <a href="{{ $backUrl }}" class="admin-show-page__back">
                        <i class="feather icon-arrow-right"></i>
                        <span>{{ __('back') }}</span>
                    </a>
                @endif
                <h2 class="admin-show-page__title">{{ $title ?? __('view') }}</h2>
            </div>
            @if (!empty($editUrl) && ($editPosition ?? 'hero') === 'hero')
                <div class="admin-show-page__hero-actions">
                    <a href="{{ $editUrl }}" class="btn btn-primary btn-sm waves-effect waves-light">
                        <i class="feather icon-edit"></i> {{ __('edit') }}
                    </a>
                </div>
            @endif
        </div>

        <div class="row match-height admin-show-page__grid">
            @foreach (($sections ?? []) as $section)
                @if (!empty($section['rows']) || !empty($section['content']))
                    <div class="{{ $section['col'] ?? (!empty($section['full']) ? 'col-12' : 'col-lg-6') }} mb-2">
                        @include('dashboard.partials.show-section', $section)
                    </div>
                @endif
            @endforeach
        </div>

        @if (!empty($editUrl) && ($editPosition ?? 'hero') === 'bottom')
            <div class="admin-show-page__actions">
                <a href="{{ $editUrl }}" class="btn btn-primary waves-effect waves-light">
                    <i class="feather icon-edit"></i> {{ __('edit') }}
                </a>
            </div>
        @endif
    </section>
</div>
