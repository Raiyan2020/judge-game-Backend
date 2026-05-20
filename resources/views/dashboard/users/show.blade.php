@extends('dashboard.layout.main')
@section('title', __('user profile') . ' - ' . $user->name)
@section('content')

    <div class="content-body">
        <section id="user-show">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('user profile') }} - {{ $user->name }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <!-- User Avatar and Basic Info -->
                                <div class="row mb-4">
                                    <div class="col-md-3 text-center">
                                        @if($user->image)
                                            <img src="{{ $user->image }}" alt="{{ $user->name }}" class="img-fluid rounded-circle" style="max-width: 200px; border: 3px solid #007bff;">
                                        @else
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto;">
                                                <i class="feather icon-user" style="font-size: 80px; color: #ccc;"></i>
                                            </div>
                                        @endif
                                        <h5 class="mt-3">{{ $user->name }}</h5>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>{{ __('phone') }}:</strong> {{ $user->full_phone ?? '-' }}</p>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>

                                <hr>

                             

                                <hr>

                    


                                <!-- Timeline Information -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5>{{ __('timeline') }}</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p><strong>{{ __('created at') }}:</strong> 
                                                    <br>{{ $user->created_at->format('Y-m-d H:i:s') }}
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>{{ __('updated at') }}:</strong> 
                                                    <br>{{ $user->updated_at->format('Y-m-d H:i:s') }}
                                                </p>
                                            </div>
                                            @if($user->deleted_at)
                                                <div class="col-md-4">
                                                    <p><strong>{{ __('deleted at') }}:</strong> 
                                                        <br>{{ $user->deleted_at->format('Y-m-d H:i:s') }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Action Buttons -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                       
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary waves-effect waves-light">
                                            <i class="feather icon-arrow-left"></i> {{ __('back') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
