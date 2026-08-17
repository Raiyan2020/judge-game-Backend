<div class="admin-form-page">
    <x-admin-form-section :title="__('basic information')" icon="icon-user" col="col-12">
        <x-text title="{{ __('name') }}" name="name" size="12" value="{{ old('name', $admin->name ?? '') }}"></x-text>
        <x-email title="{{ __('email') }}" name="email" size="12" value="{{ old('email', $admin->email ?? '') }}"></x-email>
        <x-text title="{{ __('phone') }}" name="phone" size="12" value="{{ old('phone', $admin->phone ?? '') }}"></x-text>
    </x-admin-form-section>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
