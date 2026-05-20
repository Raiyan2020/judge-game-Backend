@extends('dashboard.layout.main')
@push('styles')
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/charts/apexcharts.css') }}">
@endpush
@section('title', __('main page'))

@section('content')

    <div class="content-header row">
    </div>
    <div class="content-body">
        <!-- Dashboard Analytics Start -->
        <section id="dashboard-analytics">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="card bg-analytics text-white">
                        <div class="card-content">
                            <div class="card-body text-center">
                                <img src="{{ asset('_dashboard/app-assets/images/elements/decore-left.png') }}"
                                    class="img-left" alt="card-img-left">
                                <img src="{{ asset('_dashboard/app-assets/images/elements/decore-right.png') }}"
                                    class="img-right" alt="card-img-right">
                                <div class="avatar avatar-xl bg-primary shadow mt-0">
                                    <div class="avatar-content">
                                        <i class="feather icon-award white font-large-1"></i>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <h1 class="mb-2 text-black-50">{{ __('welcome') }} {{ auth('admin')->user()->name }}</h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card text-center">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="avatar bg-rgba-danger p-50 m-0 mb-1">
                                    <div class="avatar-content">
                                        <i class="fa fa-user text-danger fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="text-bold-700">{{ $usersCount }}</h2>
                                <p class="mb-0 line-ellipsis"> {{ __('users') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card text-center">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="avatar bg-rgba-danger p-50 m-0 mb-1">
                                    <div class="avatar-content">
                                        <i class="fa fa-product-hunt text-danger fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="text-bold-700">{{ $productsCount }}</h2>
                                <p class="mb-0 line-ellipsis"> {{ __('products') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
              
              
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card text-center">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="avatar bg-rgba-danger p-50 m-0 mb-1">
                                    <div class="avatar-content">
                                        <i class="fa fa-calendar-check text-danger fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="text-bold-700">{{ $ordersCount }}</h2>
                                <p class="mb-0 line-ellipsis"> {{ __('orders') }}</p>
                            </div>
                        </div>
                    </div>
                </div> --}}
             
                 
           

            </div>



        </section>
        <!-- Dashboard Analytics end -->
    </div>

@endsection

