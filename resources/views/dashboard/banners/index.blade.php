@extends('dashboard.layout.main')
@php
    $activeType = \App\Enums\BannerType::tryFrom((string) request('type'));
    $pageTitle = $activeType ? $activeType->label() : __('banners');
@endphp
@section('title', $pageTitle)
@section('content')

    <div class="content-body">
        <!-- Description -->
        <section id="column-selectors">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body card-dashboard">
                              
                                <div class="btn-group mb-2 mr-1" role="group">
                                    <a href="{{ route('admin.banners.index') }}"
                                        class="btn btn-{{ $activeType ? 'outline-primary' : 'primary' }} waves-effect waves-light">
                                        {{ __('all') }}
                                    </a>
                                    @foreach (\App\Enums\BannerType::cases() as $bannerType)
                                        <a href="{{ route('admin.banners.index', ['type' => $bannerType->value]) }}"
                                            class="btn btn-{{ $activeType === $bannerType ? 'primary' : 'outline-primary' }} waves-effect waves-light">
                                            {{ $bannerType->label() }}
                                        </a>
                                    @endforeach
                                </div>

                                <a href="{{ route('admin.banners.create', $activeType ? ['type' => $activeType->value] : []) }}"
                                    class="btn btn-primary mb-2 waves-effect waves-light">
                                    <i class="fas fa-plus"></i>&nbsp; {{ __('add new') }} 
                                </a>
                               
                                <div class="table-responsive">
                                    {{ $dataTable->table()}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--/ Description -->
    </div>
@endsection
@include('dashboard.layout.datatables')

@push('scripts')
    {{$dataTable->scripts()}}
    <script src="{{asset('banner/datatables/buttons.server-side.js')  }}"></script>

@endpush
