@extends('dashboard.layout.main')
@section('title', __('edit') . ' - ' . __('my profile'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <div class="admin-show-page__hero mb-2">
                <div class="admin-show-page__hero-main">
                    <a href="{{ route('admin.profile.show') }}" class="admin-show-page__back">
                        <i class="feather icon-arrow-right"></i>
                        <span>{{ __('back') }}</span>
                    </a>
                    <h2 class="admin-show-page__title">{{ __('edit') }} - {{ __('my profile') }}</h2>
                </div>
            </div>

            <div class="row match-height">
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf
                        @method('PUT')
                        @include('dashboard.profile.form')
                    </form>
                </div>

                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.profile.password') }}">
                        @csrf
                        @method('PUT')
                        @include('dashboard.profile.password-form')
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
