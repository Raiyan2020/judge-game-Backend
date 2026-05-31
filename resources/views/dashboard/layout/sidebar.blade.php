<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <ul class="nav navbar-nav flex-row " style="display: flex; justify-content: center">
        <li class="">
            <div class="" style="display: flex;justify-content: center">
                <a class=" " href="{{ route('admin.home') }}">
                    <img class="" src="" style="height: auto;width: 120px" alt="">
                    {{--   الرئيسيه --}}
                </a>
            </div>
        </li>
        <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"><i
                    class="feather icon-x d-block d-xl-none font-medium-4 primary toggle-icon"></i><i
                    class="toggle-icon feather icon-disc font-medium-4 d-none d-xl-block collapse-toggle-icon primary"
                    data-ticon="icon-disc"></i></a></li>
    </ul>

    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class=" nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                <a href="{{ route('admin.home') }}"><i class="feather icon-home">
                    </i><span class="menu-title" data-i18n="Starter kit">{{ __('main page') }}</span></a>

            </li>
            <li class=" nav-item {{ request()->is('dashboard/admins*') ? 'active' : '' }}">
                <a href="{{ route('admin.admins.index') }}"><i class="feather icon-user-check">
                    </i><span class="menu-title" data-i18n="Starter kit">{{ __('admins') }}</span></a>
            </li>
            <li class=" nav-item {{ request()->is('dashboard/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.users.index') }}"><i class="feather icon-users">
                    </i><span class="menu-title" data-i18n="Starter kit">{{ __('users') }}</span></a>
            </li>

            <li class="nav-item {{ request()->is('dashboard/banners*') ? 'active' : '' }}">
                <a href="{{ route('admin.banners.index') }}"><i class="feather icon-image">
                    </i><span class="menu-title" data-i18n="Starter kit">{{ __('banners') }}</span></a>
            </li>
            <li class=" nav-item {{ request()->is('dashboard/countries*') ? 'active' : '' }}">
                <a href="{{ route('admin.countries.index') }}"><i class="feather icon-map-pin">
                    </i><span class="menu-title" data-i18n="Starter kit">{{ __('countries') }}</span></a>

            </li>

            <li class=" nav-item {{ request()->is('dashboard/tips*') ? 'active' : '' }}">
                <a href="{{ route('admin.tips.index') }}"><i class="feather icon-info"></i><span class="menu-title"
                        data-i18n="Starter kit"> {{ __('tips') }} </span></a>
            </li>

            <li class=" nav-item {{ request()->is('dashboard/contacts*') ? 'active' : '' }}">
                <a href="{{ route('admin.contacts.index') }}"><i class="feather icon-mail"></i><span class="menu-title"
                        data-i18n="Starter kit"> {{ __('contact us') }} </span></a>

            </li>

            <li class=" nav-item {{ request()->is('dashboard/settings*') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.index') }}"><i class="feather icon-map"></i><span class="menu-title"
                        data-i18n="Starter kit"> {{ __('settings') }} </span></a>
            </li>

            <form method="POST" action="{{ route('admin.logout') }}" id="logout-form">
                @csrf
                @method('POST')
            </form>
        </ul>
    </div>
</div>
