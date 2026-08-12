@extends('dashboard.layout.main')
@section('title', __('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.banners.form')
            </form>
        </section>
    </div>
@endsection
