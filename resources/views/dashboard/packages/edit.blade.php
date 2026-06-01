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
                            <form method="POST" action="{{ route('admin.packages.update', $package) }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('dashboard.packages.form')
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
