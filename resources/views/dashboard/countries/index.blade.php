@extends('dashboard.layout.main')
@section('title',__('countries'))
@section('content')

    <div class="content-body">
        <!-- Description -->
        <section id="column-selectors">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{__('countries')}}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body card-dashboard">
                               
                                <a href="{{ route('admin.countries.create') }}"
                                    class="btn btn-primary mb-2 waves-effect waves-light">
                                    <i class="fas fa-plus"></i>&nbsp; {{__('add new')}} </a>
                              
                                <div class="table-responsive">
                                    {{ $dataTable->table()}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--/ Description -->
    </div>
@endsection
@include('dashboard.layout.datatables')

@push('scripts')
    {{$dataTable->scripts()}}
    <script src="{{asset('vendor/datatables/buttons.server-side.js')  }}"></script>

@endpush
