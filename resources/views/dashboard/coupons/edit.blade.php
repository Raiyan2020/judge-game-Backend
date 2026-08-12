@extends('dashboard.layout.main')
@section('title', __('edit'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.coupons.form')
            </form>
        </section>
    </div>
@endsection
