@extends('dashboard.layout.main')
@section('title',__('coupons'))
@section('content')

    <div class="content-body">
        <!-- Description -->
        <section id="column-selectors">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{  __('coupons') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body card-dashboard">
                              
                                <a href="{{ route('admin.coupons.create') }}"
                                    class="btn btn-primary mb-2 waves-effect waves-light">
                                    <i class="fas fa-plus"></i>&nbsp; {{ __('add new') }} 
                                </a>

                                @include('dashboard.partials.datatable-filters', [
                                    'tableId' => 'coupon-table',
                                    'filters' => [
                                        ['key' => 'code', 'label' => __('code')],
                                        ['key' => 'discount', 'label' => __('percentage')],
                                        ['key' => 'start_at', 'label' => __('start at'), 'placeholder' => 'YYYY-MM-DD'],
                                        ['key' => 'end_at', 'label' => __('end at'), 'placeholder' => 'YYYY-MM-DD'],
                                    ],
                                ])

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
