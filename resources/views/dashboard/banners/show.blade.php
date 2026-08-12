@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $banner->title)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $banner->title,
        'backUrl' => route('admin.banners.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('title') . ' (' . __('in Arabic') . ')', 'value' => $banner->getTranslation('title', 'ar')],
                    ['label' => __('title') . ' (' . __('in English') . ')', 'value' => $banner->getTranslation('title', 'en')],
                    ['label' => __('url'), 'value' => $banner->url ?: '-', 'full' => true],
                    [
                        'label' => __('status'),
                        'value' => $banner->is_active
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
                        'value' => $banner->image
                            ? view('dashboard.partials.show-image', ['url' => $banner->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
            [
                'title' => __('timeline'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('created at'), 'value' => optional($banner->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($banner->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
