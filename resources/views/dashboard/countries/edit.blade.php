@extends('dashboard.layout.main')
@section('title', __('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.countries.update', $country) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.countries.form')
            </form>
        </section>
    </div>
@endsection
