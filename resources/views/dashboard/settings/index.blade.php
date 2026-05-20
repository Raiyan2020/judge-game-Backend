@extends('dashboard.layout.main')
@section('title', __('settings'))
@section('content')

    <div class="content-body">
        <!-- Description -->
        <section id="column-selectors">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('settings') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body card-dashboard">
                                <div class="table-responsive">
                                    <table class="table table-striped dataex-html5-selectors">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('name') }}</th>
                                           <th>{{ __('actions') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                         @foreach($settings as $key=>$item)

                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$item}}</td>

                                             <td>
                                                <a class="btn btn-warning" href="{{route('admin.settings.show',$item)}}"><i class="fa fa-pencil"></i></a>
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
