@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $group->name)
@section('breadcrumbParent', __('groups'))
@section('breadcrumbParentUrl', route('admin.groups.index'))
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $group->name,
        'backUrl' => route('admin.groups.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('name'), 'value' => $group->name],
                    ['label' => __('owner'), 'value' => $group->owner?->name ?? '-'],
                    ['label' => __('members count'), 'value' => $group->accepted_users_count ?? 0],
                    ['label' => __('legal cases count'), 'value' => $group->legal_cases_count ?? 0],
                ],
            ],
            [
                'title' => __('media and images'),
                'rows' => [
                    [
                        'label' => __('image'),
                        'value' => $group->image
                            ? view('dashboard.partials.show-image', ['url' => $group->image])->render()
                            : __('no image'),
                    ],
                ],
            ],
            [
                'title' => __('description'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('description'), 'value' => $group->description ?: '-', 'full' => true],
                ],
            ],
            [
                'title' => __('timeline'),
                'col' => 'col-12',
                'rows' => [
                    ['label' => __('created at'), 'value' => optional($group->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($group->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
