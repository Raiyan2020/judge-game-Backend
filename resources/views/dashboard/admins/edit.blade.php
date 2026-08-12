@extends('dashboard.layout.main')
@section('title', __('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.admins.update', $admin) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.admins.form')
            </form>
        </section>
    </div>
@endsection
