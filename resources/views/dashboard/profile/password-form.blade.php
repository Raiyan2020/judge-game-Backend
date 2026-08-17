<div class="admin-form-page">
    <x-admin-form-section :title="__('change password')" icon="icon-lock" col="col-12">
        <x-password title="{{ __('current password') }}" name="current_password" size="12"></x-password>
        <x-password title="{{ __('new password') }}" name="password" size="12"></x-password>
        <x-password title="{{ __('confirm password') }}" name="password_confirmation" size="12"></x-password>
    </x-admin-form-section>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('change password') }}</button>
    </div>
</div>
