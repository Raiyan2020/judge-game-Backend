@extends('dashboard.layout.main')
@section('title', __('view'))
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view'),
        'backUrl' => route('admin.tips.index'),
        'sections' => [
            [
                'title' => __('content'),
                'col' => 'col-lg-7',
                'rows' => [
                    ['label' => __('description') . ' (' . __('in Arabic') . ')', 'value' => $tip->getTranslation('description', 'ar'), 'full' => true],
                    ['label' => __('description') . ' (' . __('in English') . ')', 'value' => $tip->getTranslation('description', 'en'), 'full' => true],
                    [
                        'label' => __('status'),
                        'value' => $tip->is_active
                            ? '<span class="badge badge-success">' . __('active') . '</span>'
                            : '<span class="badge badge-secondary">' . __('inactive') . '</span>',
                    ],
                ],
            ],
            [
                'title' => __('media and images'),
                'col' => 'col-lg-5',
                'rows' => [
                    [
                        'label' => __('image'),
                        'value' => $tip->image
                            ? view('dashboard.partials.show-image', ['url' => $tip->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
        ],
    ])
@endsection
