<div class="row">
    <x-text title="{{ __('name') }}" name="name" size="6" value="{{ old('name', $admin->name ?? '') }}"></x-text>
    <x-email title="{{ __('email') }}" name="email" size="6" value="{{ old('email', $admin->email ?? '') }}"></x-email>
    <x-password title="{{ __('password') }}" name="password" size="6"></x-password> 
    <x-text title="{{ __('phone') }}" name="phone" size="6" value="{{ old('phone', $admin->phone ?? '') }}"></x-text>
   
    
    @isset($admin)
    <x-select name="is_active" :items="[0 => __('inactive'), 1 => __('active')]" title="{{ __('status') }}" :selected="[$admin->is_active]" />
    @endisset
    <button type="submit" class="btn btn-success mr-1 mb-1 waves-effect waves-light">{{ __('save') }}</button>

 
</div>
   