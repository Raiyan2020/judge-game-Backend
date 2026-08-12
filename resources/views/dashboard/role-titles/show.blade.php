@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $roleTitle->title)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $roleTitle->title,
        'backUrl' => route('admin.role-titles.index'),
        'sections' => [
            [
                'title' => __('basic information'),
                'rows' => [
                    ['label' => __('title') . ' (' . __('in Arabic') . ')', 'value' => $roleTitle->getTranslation('title', 'ar')],
                    ['label' => __('title') . ' (' . __('in English') . ')', 'value' => $roleTitle->getTranslation('title', 'en')],
                    ['label' => __('role'), 'value' => __($roleTitle->role)],
                    ['label' => __('tier'), 'value' => $roleTitle->tier ?? '-'],
                    ['label' => __('points'), 'value' => $roleTitle->reward_points ?? '-'],
                ],
            ],
            [
                'title' => __('required actions'),
                'col' => 'col-12',
                'content' => view('dashboard.role-titles.show-requirements', ['roleTitle' => $roleTitle])->render(),
            ],
        ],
    ])
@endsection
