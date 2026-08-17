@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . __('points') . ' - ' . __($role))
@section('breadcrumbParent', __('points'))
@section('breadcrumbParentUrl', route('admin.role-actions.index'))
@section('content')
    @php
        $earnedActions = $role === 'citizen'
            ? $actions->reject(fn ($action) => $action->isCitizenAgainstAction())->values()
            : $actions;
        $againstActions = $role === 'citizen'
            ? $actions->filter(fn ($action) => $action->isCitizenAgainstAction())->values()
            : collect();

        $earnedRows = $earnedActions->map(fn ($action) => [
            'label' => $action->localizedTitle(),
            'value' => (int) $action->points,
        ])->all();

        $againstRows = $againstActions->map(fn ($action) => [
            'label' => $action->localizedTitle(),
            'value' => (int) $action->points,
        ])->all();

        $sections = [];

        if ($earnedRows) {
            $sections[] = [
                'title' => $role === 'citizen' ? __('citizen earned points') : __('points'),
                'rows' => $earnedRows,
            ];
        }

        if ($againstRows) {
            $sections[] = [
                'title' => __('citizen against points'),
                'rows' => $againstRows,
            ];
        }
    @endphp

    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . __('points') . ' - ' . __($role),
        'backUrl' => route('admin.role-actions.index'),
        'editUrl' => route('admin.role-actions.edit', $role),
        'sections' => $sections,
    ])
@endsection
