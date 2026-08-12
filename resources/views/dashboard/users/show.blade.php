@extends('dashboard.layout.main')
@section('title', __('user profile') . ' - ' . $user->name)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('user profile') . ' - ' . $user->name,
        'backUrl' => route('admin.users.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name'), 'value' => $user->name],
                    ['label' => __('user name'), 'value' => $user->username ?? '-'],
                    ['label' => __('nickname'), 'value' => $user->nickname ?? '-'],
                    ['label' => __('gender'), 'value' => $user->gender ? __($user->gender) : '-'],
                    ['label' => __('status'), 'value' => $user->status ? __($user->status) : '-'],
                ],
            ],
            [
                'title' => __('contact information'),
                'rows' => [
                    ['label' => __('phone code'), 'value' => $user->country_code ?? '-'],
                    ['label' => __('phone'), 'value' => $user->phone ?? '-'],
                    ['label' => __('full phone'), 'value' => $user->full_phone ?? '-'],
                    ['label' => __('language'), 'value' => $user->language ? __($user->language) : '-'],
                ],
            ],
            [
                'title' => __('media and images'),
                'col' => 'col-lg-6',
                'rows' => [
                    [
                        'label' => __('image'),
                        'value' => $user->image
                            ? view('dashboard.partials.show-image', ['url' => $user->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
            [
                'title' => __('timeline'),
                'col' => 'col-lg-6',
                'rows' => [
                    ['label' => __('created at'), 'value' => optional($user->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($user->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
