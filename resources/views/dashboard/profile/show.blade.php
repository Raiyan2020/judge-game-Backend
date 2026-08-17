@extends('dashboard.layout.main')
@section('title', __('my profile'))
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('my profile') . ' - ' . $admin->name,
        'editUrl' => route('admin.profile.edit'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name'), 'value' => $admin->name],
                    ['label' => __('email'), 'value' => $admin->email ?? '-'],
                    ['label' => __('phone'), 'value' => $admin->phone],
                ],
            ],
            [
                'title' => __('timeline'),
                'rows' => [
                    ['label' => __('created at'), 'value' => optional($admin->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($admin->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
