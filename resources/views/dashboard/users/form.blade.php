<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-user" col="col-lg-6">
            <x-text title="{{ __('name') }}" name="name" size="12" value="{{ old('name', $user->name ?? '') }}"></x-text>
            <x-select name="profile_type" :items="$profileTypes" size="12" title="{{ __('profile_type') }}" :selected="[old('profile_type', $user->profile_type ?? null)]" />
        </x-admin-form-section>

        <x-admin-form-section :title="__('contact information')" icon="icon-phone" col="col-lg-6">
            <x-select name="country_code" :items="$countryCodes" size="6" title="{{ __('phone code') }}" :selected="[old('country_code', $user->country_code ?? null)]" />
            <x-text title="{{ __('phone') }}" name="phone" size="6" value="{{ old('phone', $user->phone ?? '') }}"></x-text>
            <x-select name="whatsapp_country_code" :items="$whatsappCountryCodes" size="6" title="{{ __('whatsapp code') }}" :selected="[old('whatsapp_country_code', $user->whatsapp_country_code ?? '')]" />
            <x-text title="{{ __('whatsapp') }}" name="whatsapp" size="6" value="{{ old('whatsapp', $user->whatsapp ?? '') }}"></x-text>
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-12">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200" accept="image/*"
                        {{ @$user->image ? 'data-default-file=' . $user->image . '' : '' }}>
                    @error('image')
                        <span style="color: red">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-admin-form-section>
    </div>

    <div class="mt-1 mb-1">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>
