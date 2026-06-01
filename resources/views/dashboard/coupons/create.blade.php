@extends('dashboard.layout.main')
@section('title', __('add new'))
@section('content')
    <div class="content-body">
        <section id="basic-input">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('add new') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <a  href="{{route('admin.coupons.index')}}" class="btn btn-primary mb-2 waves-effect waves-light">&nbsp; {{ __('show all') }} </a>
                                <form method="POST" action="{{ route('admin.coupons.store') }}" enctype="multipart/form-data">
                                    @csrf                              
                                    @include('dashboard.coupons.form')
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
