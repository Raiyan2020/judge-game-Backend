{{--
    Shared create / edit form for dashboard users.

    Every input below maps to a real `users` column. The previous version posted
    `profile_type`, `whatsapp` and `whatsapp_country_code` — columns this project
    has never had — and never posted `username`, which is NOT NULL + UNIQUE, so
    the form could not be saved. `full_phone` is a generated column and is shown
    read-only on the view page instead of being edited here.
--}}
<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-user" col="col-lg-6">
            <x-text title="{{ __('name') }}" name="name" size="12"
                value="{{ old('name', $user->name ?? '') }}"></x-text>

            <x-text title="{{ __('user name') }}" name="username" size="6"
                value="{{ old('username', $user->username ?? '') }}"></x-text>

            <x-text title="{{ __('nickname') }}" name="nickname" size="6"
                value="{{ old('nickname', $user->nickname ?? '') }}"></x-text>

            <x-select name="gender" :items="$genders" size="6" title="{{ __('gender') }}"
                :selected="[old('gender', $user->gender ?? '')]" />

            <x-date title="{{ __('birthdate') }}" name="birthdate" size="6"
                value="{{ old('birthdate', isset($user->birthdate) ? substr((string) $user->birthdate, 0, 10) : '') }}" />

            <x-select name="status" :items="$statuses" size="6" title="{{ __('status') }}"
                :selected="[old('status', $user->status ?? $defaults['status'])]" />

            <x-select name="language" :items="$languages" size="6" title="{{ __('language') }}"
                :selected="[old('language', $user->language ?? $defaults['language'])]" />
        </x-admin-form-section>

        <x-admin-form-section :title="__('contact information')" icon="icon-phone" col="col-lg-6">
            <x-select name="country_code" :items="$phoneCodes" size="6" title="{{ __('phone code') }}"
                :selected="[old('country_code', $user->country_code ?? '')]" />

            <x-text title="{{ __('phone') }}" name="phone" size="6" type="tel" inputmode="numeric"
                value="{{ old('phone', $user->phone ?? '') }}"></x-text>

            <x-select name="country_id" :items="$countries" size="12" title="{{ __('country') }}"
                :selected="[old('country_id', $user->country_id ?? '')]" />
        </x-admin-form-section>

        <x-admin-form-section :title="__('media and images')" icon="icon-image" col="col-12">
            <div class="col-12">
                <div class="form-group">
                    <label>{{ __('image') }}</label>
                    <input type="file" name="image" class="dropify" data-height="200"
                        accept="image/jpeg,image/png,image/gif,image/webp"
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
