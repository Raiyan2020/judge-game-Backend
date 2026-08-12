@extends('dashboard.layout.main')
@section('title', __('settings'))
@section('content')
    <div class="content-body">
        <section id="basic-input" class="admin-form-page">
            <form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row match-height">
                    <x-admin-form-section :title="__($settings_page)" icon="icon-settings" col="col-12">
                        @foreach ($settings as $setting)
                            @if ($setting->type == 'long_text')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ $setting->title }} {{ __('in Arabic') }}</label>
                                        <textarea name="{{ $setting->name }}[ar]" class="form-control ckeditor">{{ $setting->getTranslation('value', 'ar') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ $setting->title }} {{ __('in English') }}</label>
                                        <textarea name="{{ $setting->name }}[en]" class="form-control ckeditor">{{ $setting->getTranslation('value', 'en') }}</textarea>
                                    </div>
                                </div>
                            @elseif($setting->type == 'url' || $setting->type == 'number' || $setting->type == 'text' || $setting->type == 'email')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ $setting->title }}</label>
                                        <input type="text" name="{{ $setting->name }}[en]" value="{{ $setting->getTranslation('value', 'en') }}" class="form-control">
                                    </div>
                                </div>
                            @elseif($setting->type == 'checkbox')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ $setting->title }}</label>
                                        <input type="hidden" name="{{ $setting->name }}[en]" value="0">
                                        <input type="checkbox" name="{{ $setting->name }}[en]" value="1" {{ $setting->getTranslation('value', 'en') == true ? 'checked' : '' }} class="field">
                                    </div>
                                </div>
                            @elseif($setting->type == 'radio')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ $setting->title }}</label>
                                        <div>
                                            <input type="radio" name="{{ $setting->name }}[en]" value="1" class="field" {{ $setting->getTranslation('value', 'en') == "1" ? 'checked' : '' }}>
                                            {{ __('active') }}
                                            <input type="radio" name="{{ $setting->name }}[en]" value="0" class="field" {{ $setting->getTranslation('value', 'en') == "0" ? 'checked' : '' }}>
                                            {{ __('inactive') }}
                                        </div>
                                    </div>
                                </div>
                            @elseif($setting->type == 'file')
                                @php
                                    $logoPath = $setting->getTranslation('value', 'en');
                                    $logoUrl = $logoPath ? asset(ltrim($logoPath, '/')) : '';
                                @endphp
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ __($setting->title) }}</label>
                                        <input type="file" name="{{ $setting->name }}" class="dropify" data-height="200" accept="image/*"
                                            @if($logoUrl) data-default-file="{{ $logoUrl }}" @endif>
                                        @error($setting->name)
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </x-admin-form-section>
                </div>
                <div class="mt-1 mb-1">
                    <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
                </div>
            </form>
        </section>
    </div>
@endsection
