@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $package->name)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $package->name,
        'backUrl' => route('admin.packages.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name') . ' (' . __('in Arabic') . ')', 'value' => $package->getTranslation('name', 'ar')],
                    ['label' => __('name') . ' (' . __('in English') . ')', 'value' => $package->getTranslation('name', 'en')],
                    [
                        'label' => __('status'),
                        'value' => $package->is_active
                            ? '<span class="badge badge-success">' . __('active') . '</span>'
                            : '<span class="badge badge-secondary">' . __('inactive') . '</span>',
                    ],
                    [
                        'label' => __('most sale'),
                        'value' => $package->most_sale
                            ? '<span class="badge badge-warning">' . __('yes') . '</span>'
                            : __('no'),
                    ],
                ],
            ],
            [
                'title' => __('pricing details'),
                'rows' => [
                    ['label' => __('price'), 'value' => format_package_price($package->price)],
                    ['label' => __('duration in days'), 'value' => $package->duration_days],
                ],
            ],
            [
                'title' => __('description'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('description') . ' (' . __('in Arabic') . ')', 'value' => $package->getTranslation('description', 'ar'), 'full' => true],
                    ['label' => __('description') . ' (' . __('in English') . ')', 'value' => $package->getTranslation('description', 'en'), 'full' => true],
                ],
            ],
            [
                'title' => __('timeline'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('created at'), 'value' => optional($package->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($package->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
