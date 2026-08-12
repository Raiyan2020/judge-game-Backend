@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $admin->name)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $admin->name,
        'backUrl' => route('admin.admins.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name'), 'value' => $admin->name],
                    ['label' => __('email'), 'value' => $admin->email],
                    ['label' => __('phone'), 'value' => $admin->phone],
                    [
                        'label' => __('status'),
                        'value' => $admin->is_active
                            ? '<span class="badge badge-success">' . __('active') . '</span>'
                            : '<span class="badge badge-secondary">' . __('inactive') . '</span>',
                    ],
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
