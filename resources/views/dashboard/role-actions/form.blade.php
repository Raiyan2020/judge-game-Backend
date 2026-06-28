@extends('dashboard.layout.main')
@section('title', __('points'))
@section('content')
    <div class="content-body">
        <section id="basic-input">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"> {{ __('points') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <form action="{{ route('admin.role-actions.store') }}" method="POST">
                                    @csrf

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="60%">{{ __('action') }}</th>
                                                    <th width="25%">{{ __('points') }}</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @forelse($actions as $index => $action)
                                                    <tr>
                                                        <td>
                                                            {{ $action->title }}

                                                            <input type="hidden" name="actions[{{ $index }}][id]"
                                                                value="{{ $action->id }}">
                                                        </td>

                                                        <td>
                                                            <input type="number" class="form-control"
                                                                name="actions[{{ $index }}][points]"
                                                                value="{{ old("actions.$index.points", $action->points) }}"
                                                                min="0">
                                                        </td>

                                                       
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center">
                                                            {{ __('No data found') }}
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">
                                        {{ __('save') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
