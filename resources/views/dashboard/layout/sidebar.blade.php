<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">

    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="{{ __('Collapse menu') }}">
        <i class="feather icon-chevron-left"></i>
    </button>

    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item sidebar-logo-li">
                <a class="sidebar-logo navbar-brand judge-brand-logo" href="{{ route('admin.home') }}">
                    <span class="judge-brand-logo__ring judge-brand-logo__ring--1" aria-hidden="true"></span>
                    <span class="judge-brand-logo__ring judge-brand-logo__ring--2" aria-hidden="true"></span>
                    <img class="judge-brand-logo__img"
                        src="{{ dashboard_logo_url() }}?v={{ dashboard_logo_version() }}"
                        alt="JUDGE"
                        width="96"
                        height="96">
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-search">
        <i class="feather icon-search sidebar-search-icon"></i>
        <input type="text" id="sidebarSearch" placeholder="{{ __('Search') }}..." autocomplete="off">
    </div>

    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                <a href="{{ route('admin.home') }}">
                    <i class="feather icon-home"></i>
                    <span class="menu-title">{{ __('main page') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/admins*') ? 'active' : '' }}">
                <a href="{{ route('admin.admins.index') }}">
                    <i class="feather icon-user-check"></i>
                    <span class="menu-title">{{ __('admins') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.users.index') }}">
                    <i class="feather icon-users"></i>
                    <span class="menu-title">{{ __('users') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/banners*') ? 'active' : '' }}">
                <a href="{{ route('admin.banners.index') }}">
                    <i class="feather icon-image"></i>
                    <span class="menu-title">{{ __('banners') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/countries*') ? 'active' : '' }}">
                <a href="{{ route('admin.countries.index') }}">
                    <i class="feather icon-map-pin"></i>
                    <span class="menu-title">{{ __('countries') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/tips*') ? 'active' : '' }}">
                <a href="{{ route('admin.tips.index') }}">
                    <i class="feather icon-info"></i>
                    <span class="menu-title">{{ __('tips') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/last-updates*') ? 'active' : '' }}">
                <a href="{{ route('admin.last-updates.index') }}">
                    <i class="feather icon-clock"></i>
                    <span class="menu-title">{{ __('last updates') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/coupons*') ? 'active' : '' }}">
                <a href="{{ route('admin.coupons.index') }}">
                    <i class="feather icon-tag"></i>
                    <span class="menu-title">{{ __('coupons') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/packages*') ? 'active' : '' }}">
                <a href="{{ route('admin.packages.index') }}">
                    <i class="feather icon-package"></i>
                    <span class="menu-title">{{ __('packages') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/subscriptions*') ? 'active' : '' }}">
                <a href="{{ route('admin.subscriptions.index') }}">
                    <i class="feather icon-check-circle"></i>
                    <span class="menu-title">{{ __('subscriptions') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/groups*') ? 'active' : '' }}">
                <a href="{{ route('admin.groups.index') }}">
                    <i class="feather icon-users"></i>
                    <span class="menu-title">{{ __('groups') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/role-actions*') ? 'active' : '' }}">
                <a href="{{ route('admin.role-actions.index') }}">
                    <i class="feather icon-award"></i>
                    <span class="menu-title">{{ __('points') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/role-titles*') ? 'active' : '' }}">
                <a href="{{ route('admin.role-titles.index') }}">
                    <i class="feather icon-bookmark"></i>
                    <span class="menu-title">{{ __('titles') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/contacts*') ? 'active' : '' }}">
                <a href="{{ route('admin.contacts.index') }}">
                    <i class="feather icon-mail"></i>
                    <span class="menu-title">{{ __('contact us') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->is('dashboard/settings*') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.index') }}">
                    <i class="feather icon-settings"></i>
                    <span class="menu-title">{{ __('settings') }}</span>
                </a>
            </li>

            <form method="POST" action="{{ route('admin.logout') }}" id="logout-form">
                @csrf
                @method('POST')
            </form>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var SIDEBAR_KEY = 'judge_admin_sidebar_collapsed';
    var collapseBtn = document.getElementById('sidebarCollapseBtn');

    function isRtl() {
        return document.documentElement.getAttribute('dir') === 'rtl' ||
            document.documentElement.getAttribute('data-textdirection') === 'rtl';
    }

    function isCollapsed() {
        return document.body.classList.contains('menu-collapsed');
    }

    function persistSidebarState() {
        try {
            localStorage.setItem(SIDEBAR_KEY, isCollapsed() ? '1' : '0');
        } catch (e) {}
    }

    function syncChevron() {
        if (!collapseBtn) return;
        var icon = collapseBtn.querySelector('i');
        if (!icon) return;

        var collapsed = isCollapsed();
        icon.className = 'feather';

        if (isRtl()) {
            icon.classList.add(collapsed ? 'icon-chevron-left' : 'icon-chevron-right');
        } else {
            icon.classList.add(collapsed ? 'icon-chevron-right' : 'icon-chevron-left');
        }

        collapseBtn.setAttribute(
            'title',
            collapsed ? '{{ __('Expand menu') }}' : '{{ __('Collapse menu') }}'
        );
    }

    function clearHoverExpanded() {
        if (typeof jQuery === 'undefined') return;
        jQuery('.main-menu, .navbar-header').removeClass('expanded');
    }

    function applyExpandedFallback() {
        document.body.classList.remove('menu-collapsed');
        document.body.classList.add('menu-expanded');
    }

    function applyCollapsedFallback() {
        document.body.classList.remove('menu-expanded');
        document.body.classList.add('menu-collapsed');
    }

    function toggleSidebar() {
        clearHoverExpanded();

        if (typeof jQuery !== 'undefined' && jQuery.app && jQuery.app.menu) {
            jQuery.app.menu.toggle();
        } else if (isCollapsed()) {
            applyExpandedFallback();
        } else {
            applyCollapsedFallback();
        }

        setTimeout(function () {
            persistSidebarState();
            syncChevron();
            if (typeof jQuery !== 'undefined') {
                jQuery(window).trigger('resize');
            }
        }, 220);
    }

    try {
        if (localStorage.getItem(SIDEBAR_KEY) === null) {
            applyExpandedFallback();
            localStorage.setItem(SIDEBAR_KEY, '0');
        }
    } catch (e) {}

    syncChevron();

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.menu-toggle, .modern-nav-toggle');
        if (!toggle || toggle.id === 'sidebarCollapseBtn') return;

        setTimeout(function () {
            persistSidebarState();
            syncChevron();
        }, 220);
    });

    var searchInput = document.getElementById('sidebarSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        var nav = document.getElementById('main-menu-navigation');
        if (!nav) return;

        nav.querySelectorAll(':scope > li').forEach(function (li) {
            if (!query) {
                li.style.display = '';
                return;
            }

            var directAnchor = li.querySelector(':scope > a');
            var directText = directAnchor ? directAnchor.textContent.toLowerCase() : '';
            li.style.display = directText.indexOf(query) !== -1 ? '' : 'none';
        });
    });
});
</script>
