@extends('dashboard.layout.main')
@section('title', __('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.role-titles.update', $roleTitle) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.role-titles.form')
            </form>
        </section>
    </div>
@endsection
