@extends('dashboard.layout.main')
@section('title', __('add new'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.role-titles.store') }}" enctype="multipart/form-data">
                @csrf
                @include('dashboard.role-titles.form')
            </form>
        </section>
    </div>
@endsection
