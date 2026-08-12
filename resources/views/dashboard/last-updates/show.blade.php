@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $lastUpdate->title)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $lastUpdate->title,
        'backUrl' => route('admin.last-updates.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('title') . ' (' . __('in Arabic') . ')', 'value' => $lastUpdate->getTranslation('title', 'ar')],
                    ['label' => __('title') . ' (' . __('in English') . ')', 'value' => $lastUpdate->getTranslation('title', 'en')],
                    ['label' => __('version'), 'value' => $lastUpdate->version],
                    ['label' => __('display speed'), 'value' => $lastUpdate->display_speed],
                    [
                        'label' => __('status'),
                        'value' => $lastUpdate->is_active
                            ? '<span class="badge badge-success">' . __('active') . '</span>'
                            : '<span class="badge badge-secondary">' . __('inactive') . '</span>',
                    ],
                ],
            ],
            [
                'title' => __('media and images'),
                'rows' => [
                    [
                        'label' => __('image'),
                        'value' => $lastUpdate->image
                            ? view('dashboard.partials.show-image', ['url' => $lastUpdate->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
            [
                'title' => __('description'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('description') . ' (' . __('in Arabic') . ')', 'value' => $lastUpdate->getTranslation('description', 'ar'), 'full' => true],
                    ['label' => __('description') . ' (' . __('in English') . ')', 'value' => $lastUpdate->getTranslation('description', 'en'), 'full' => true],
                ],
            ],
        ],
    ])
@endsection
