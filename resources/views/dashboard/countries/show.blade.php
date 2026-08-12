@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $country->name)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $country->name,
        'backUrl' => route('admin.countries.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name') . ' (' . __('in Arabic') . ')', 'value' => $country->getTranslation('name', 'ar')],
                    ['label' => __('name') . ' (' . __('in English') . ')', 'value' => $country->getTranslation('name', 'en')],
                    ['label' => __('phone code'), 'value' => $country->country_code],
                    [
                        'label' => __('status'),
                        'value' => $country->is_active
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
                        'value' => $country->image
                            ? view('dashboard.partials.show-image', ['url' => $country->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
        ],
    ])
@endsection
