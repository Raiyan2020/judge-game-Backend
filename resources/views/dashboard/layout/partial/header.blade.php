<!DOCTYPE html>
<html class="loading" lang="{{ app()->getLocale() }}" data-textdirection="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JUDGE | @yield('title')</title>

    @include('dashboard.layout.styles')
    @stack('styles')
    <script>
        (function () {
            try {
                var color = localStorage.getItem('theme-primary-color') || '#c4a35a';
                var hover = localStorage.getItem('theme-primary-hover') || '#a8893e';
                var r = parseInt(color.slice(1, 3), 16);
                var g = parseInt(color.slice(3, 5), 16);
                var b = parseInt(color.slice(5, 7), 16);
                document.documentElement.style.setProperty('--primary', color);
                document.documentElement.style.setProperty('--primary-hover', hover);
                document.documentElement.style.setProperty('--primary-rgb', r + ', ' + g + ', ' + b);
                document.documentElement.style.setProperty('--primary-ui', color);
                document.documentElement.style.setProperty('--primary-ui-hover', hover);
                document.documentElement.style.setProperty('--primary-ui-rgb', r + ', ' + g + ', ' + b);
            } catch (e) {}
        })();
    </script>
</head>

<body
    @if (app()->getLocale() === 'ar')
        style="font-family: 'Almarai', sans-serif !important; font-weight: 400;"
    @else
        style="font-family: 'Cairo', sans-serif !important; font-weight: 500;"
    @endif
    id="content_body"
    class="position-relative vertical-layout vertical-menu-modern 2-columns navbar-floating footer-static dark-layout"
    data-open="click"
    data-menu="vertical-menu-modern"
    data-col="2-columns"
    data-type="dark">

    <script>
        (function () {
            try {
                var collapsed = localStorage.getItem('judge_admin_sidebar_collapsed') === '1';
                document.body.classList.add(collapsed ? 'menu-collapsed' : 'menu-expanded');

                var layout = localStorage.getItem('judge_currentLayout');
                if (layout === 'light') {
                    document.body.classList.remove('dark-layout');
                    document.body.classList.add('light-mode');
                    document.body.dataset.type = 'light';
                } else {
                    document.body.classList.add('dark-layout');
                    document.body.classList.remove('light-mode');
                    document.body.dataset.type = 'dark';
                }
            } catch (e) {
                document.body.classList.add('menu-expanded');
            }
        })();
    </script>
    <div class="loader">
        <div class="sk-chase">
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
        </div>
    </div>
    @include('dashboard.layout.partial.premium-bg')
