@extends('dashboard.layout.main')
@section('title', __('settings'))
@section('content')
    <div class="content-body">
        <section id="basic-input">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"> {{ __('settings') }}</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <form action="{{ route('admin.settings.store') }}" method="POST">
                                    @csrf
                                    @foreach ($settings as $setting)
                                        @if ($setting->type == 'long_text')
                                            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                                <label class="form-label">{{ $setting->title }} {{ __('in Arabic') }}</label>
                                                <div class="form-line">
                                                    <textarea name="{{ $setting->name }}[ar]" class="form-control ckeditor">{{ $setting->getTranslation('value', 'ar') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                                <label class="form-label">{{ $setting->title }} {{ __('in English') }}</label>
                                                <div class="form-line">
                                                    <textarea name="{{ $setting->name }}[en]" class="form-control ckeditor">{{ $setting->getTranslation('value', 'en') }}</textarea>
                                                </div>
                                            </div>
                                        @elseif($setting->type == 'url' || $setting->type == 'number' || $setting->type == 'text' || $setting->type == 'email')
                                            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                                <label class="form-label">{{ $setting->title }}</label>
                                                <div class="form-line">
                                                    <input type="text" name="{{ $setting->name }}[en]" value="{{ $setting->getTranslation('value', 'en') }}" class="form-control">
                                                </div>
                                            </div>
                                        @elseif($setting->type == 'checkbox')
                                            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                                <label class="form-label">{{ $setting->title }}</label>
                                                <div class="form-line">
                                                    <input type="hidden" name="{{ $setting->name }}[en]" value="0">
                                                    <input type="checkbox" name="{{ $setting->name }}[en]" value="1" {{ $setting->getTranslation('value', 'en') == true ? 'checked' : '' }} class="field">
                                                </div>
                                            </div>
                                            @elseif($setting->type == 'radio')
                                            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                                <label class="form-label">{{ $setting->title }}</label>
                                                <div class="form-line">
                                                    <input 
                                                        type="radio" 
                                                        name="{{ $setting->name }}[en]" 
                                                        value="1" 
                                                        class="field" 
                                                        {{ $setting->getTranslation('value', 'en') == "1" ? 'checked' : '' }}
                                                    > 
                                                    {{ __('active') }}
                                            
                                                    <input 
                                                        type="radio" 
                                                        name="{{ $setting->name }}[en]" 
                                                        value="0" 
                                                        class="field" 
                                                        {{ $setting->getTranslation('value', 'en') == "0" ? 'checked' : '' }}
                                                    > 
                                                    {{ __('inactive') }}
                                                </div>
                                            </div>
                                            
                                        @endif
                                    @endforeach
                                    <button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
