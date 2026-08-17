<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-user" col="col-lg-6">
            <x-text title="{{ __('name') }}" name="name" size="12" value="{{ old('name', $admin->name ?? '') }}"></x-text>
            <x-email title="{{ __('email') }}" name="email" size="12" value="{{ old('email', $admin->email ?? '') }}"></x-email>
        </x-admin-form-section>

        <x-admin-form-section :title="__('account security')" icon="icon-lock" col="col-lg-6">
            <x-password title="{{ __('password') }}" name="password" size="12"></x-password>
            <x-text title="{{ __('phone') }}" name="phone" size="12" value="{{ old('phone', $admin->phone ?? '') }}"></x-text>
            @isset($admin)
                <x-select name="is_active" :items="[0 => __('inactive'), 1 => __('active')]" title="{{ __('status') }}" size="12" :selected="[$admin->is_active]" />
            @endisset
        </x-admin-form-section>
    </div>

    <div class="admin-form-page__actions">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
