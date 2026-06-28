@extends('dashboard.layout.main')
@section('title', __('points'))
@section('content')

    <div class="content-body">
        <!-- Description -->
        <section id="column-selectors">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('points') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body card-dashboard">
                                <div class="table-responsive">
                                    <table class="table table-striped dataex-html5-selectors">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('role') }}</th>
                                           <th>{{ __('points') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                         @foreach($roles as $item)

                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{__($item)}}</td>

                                            <td>
                                                <a class="btn btn-warning" href="{{route('admin.role-actions.edit',$item)}}"><i class="fa fa-pencil"></i></a>
                                            </td> 
                                        </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
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
