@extends('dashboard.layout.main')
@section('title',__('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"> {{__('edit')}}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <a  href="{{route('admin.admins.index')}}" class="btn btn-primary mb-2 waves-effect waves-light">&nbsp; كل المديرين </a>
                                <form method="POST" action="{{ route('admin.admins.update', $admin) }}"  enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    @include('dashboard.admins.form')
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
