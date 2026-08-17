@extends('dashboard.layout.main')
@section('title', __('edit') . ' - ' . __('points') . ' - ' . __($role))
@section('breadcrumbParent', __('points'))
@section('breadcrumbParentUrl', route('admin.role-actions.index'))
@section('content')
    @php
        $inputIndex = 0;
        $earnedActions = $role === 'citizen'
            ? $actions->reject(fn ($action) => $action->isCitizenAgainstAction())->values()
            : $actions;
        $againstActions = $role === 'citizen'
            ? $actions->filter(fn ($action) => $action->isCitizenAgainstAction())->values()
            : collect();
    @endphp

    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <div class="admin-show-page__hero mb-2">
                <div class="admin-show-page__hero-main">
                    <a href="{{ route('admin.role-actions.show', $role) }}" class="admin-show-page__back">
                        <i class="feather icon-arrow-right"></i>
                        <span>{{ __('back') }}</span>
                    </a>
                    <h2 class="admin-show-page__title">{{ __('edit') }} - {{ __('points') }} - {{ __($role) }}</h2>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <div class="card-body">
                        <form action="{{ route('admin.role-actions.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="{{ $role }}">

                            @if ($earnedActions->isNotEmpty())
                                @include('dashboard.role-actions.partials.actions-table', [
                                    'actions' => $earnedActions,
                                    'startIndex' => $inputIndex,
                                    'sectionTitle' => $role === 'citizen' ? __('citizen earned points') : null,
                                ])
                                @php $inputIndex += $earnedActions->count(); @endphp
                            @endif

                            @if ($againstActions->isNotEmpty())
                                @include('dashboard.role-actions.partials.actions-table', [
                                    'actions' => $againstActions,
                                    'startIndex' => $inputIndex,
                                    'sectionTitle' => __('citizen against points'),
                                    'sectionHint' => __('citizen against points hint'),
                                    'hideHeader' => $earnedActions->isNotEmpty(),
                                ])
                            @endif

                            @if ($actions->isEmpty())
                                <p class="text-center mb-0">{{ __('No data found') }}</p>
                            @endif

                            <div class="admin-form-page__actions">
                                <button type="submit" class="btn btn-success waves-effect waves-light">
                                    {{ __('save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
