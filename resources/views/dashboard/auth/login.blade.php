<!DOCTYPE html>
<html class="loading" lang="{{ app()->getLocale() }}" data-textdirection="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JUDGE | {{ __('login') }}</title>

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/vendors-rtl.min.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/bootstrap.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/components.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/themes/dark-layout.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/custom-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/vendors.min.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/bootstrap.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/components.css') }}">
        <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/themes/dark-layout.css') }}">
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if (app()->getLocale() === 'ar')
        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('_dashboard/assets/css/login.css') }}?v={{ @filemtime(public_path('_dashboard/assets/css/login.css')) }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/assets/css/judge-brand-animation.css') }}?v={{ @filemtime(public_path('_dashboard/assets/css/judge-brand-animation.css')) }}">
</head>

<body id="content_body" class="login-page vertical-layout vertical-menu-modern 1-column blank-page dark-layout" data-open="click" data-menu="vertical-menu-modern" data-col="1-column" data-type="dark"
    style="font-family: {{ app()->getLocale() === 'ar' ? "'Almarai'" : "'Cairo'" }}, sans-serif !important;">

<canvas id="stars-canvas"></canvas>
<div class="cosmic-nebula cosmic-nebula-1"></div>
<div class="cosmic-nebula cosmic-nebula-2"></div>

<svg class="constellation" viewBox="0 0 1920 1080" preserveAspectRatio="xMidYMid slice">
    <line x1="1600" y1="200" x2="1700" y2="350" />
    <line x1="1700" y1="350" x2="1650" y2="500" />
    <line x1="1650" y1="500" x2="1750" y2="600" />
    <line x1="1750" y1="600" x2="1680" y2="750" />
    <circle cx="1600" cy="200" r="3" />
    <circle cx="1700" cy="350" r="2.5" />
    <circle cx="1650" cy="500" r="2" />
    <circle cx="1750" cy="600" r="3" />
    <circle cx="1680" cy="750" r="2" />
</svg>

<div class="login-controls">
    <a class="login-control-btn login-lang-toggle"
       aria-label="{{ app()->getLocale() === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}"
       href="{{ route('change-language', ['lang' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}">
        @if (app()->getLocale() === 'ar')
            <img src="{{ asset('_dashboard/assets/flags/svg/us.svg') }}" alt="US">
            <span class="lang-label">{{ __('English') }}</span>
        @else
            <img src="{{ asset('_dashboard/assets/flags/svg/sa.svg') }}" alt="SA">
            <span class="lang-label">{{ __('Arabic') }}</span>
        @endif
    </a>
    <a class="login-control-btn" href="#" id="layout-mode-login" title="{{ __('Theme mode') }}">
        <i class="ficon feather icon-sun"></i>
    </a>
</div>

<div class="login-page-wrapper">
    <div class="login-card">
        <div class="login-brand judge-brand-logo judge-brand-logo--login">
            <span class="judge-brand-logo__ring judge-brand-logo__ring--1" aria-hidden="true"></span>
            <span class="judge-brand-logo__ring judge-brand-logo__ring--2" aria-hidden="true"></span>
            <img src="{{ dashboard_logo_url() }}?v={{ dashboard_logo_version() }}"
                 alt="Judge Game"
                 class="judge-brand-logo__img login-brand-logo"
                 width="120"
                 height="120">
        </div>

        <div class="login-header">
            <h4>{{ __('Welcome to Judge control panel') }}</h4>
            <span class="login-tagline login-tagline--animated">JUSTICE SHALL PREVAIL</span>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session()->has('fail'))
            <div class="alert alert-danger">{{ session('fail') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="login-form" class="form-horizontal" action="{{ route('admin.login') }}" method="post" novalidate>
            @csrf

            <div class="modern-input-group">
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope field-icon field-icon-end"></i>
                    <input type="email" id="email" class="modern-input" placeholder="{{ __('email') }}" name="email" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="modern-input-group">
                <div class="input-icon-wrap">
                    <i class="fas fa-lock field-icon field-icon-end"></i>
                    <button type="button" class="field-icon field-icon-start toggle-password" aria-label="Toggle password">
                        <i class="fas fa-eye" id="toggle-password-icon"></i>
                    </button>
                    <input type="password" id="password" class="modern-input" name="password" placeholder="{{ __('password') }}" required style="padding-inline-start: 2.75rem;">
                </div>
            </div>

            <button type="submit" class="modern-btn submit_button">
                <span>{{ __('login') }}</span>
                <i class="fas {{ app()->getLocale() === 'ar' ? 'fa-arrow-left' : 'fa-arrow-right' }} btn-arrow"></i>
            </button>
        </form>
    </div>
</div>

<script src="{{ asset('_dashboard/app-assets/vendors/js/vendors.min.js') }}"></script>
<script>
(function () {
    function updateLoginThemeIcon() {
        var isDark = localStorage.getItem('judge_currentLayout') !== 'light';
        var iconClass = isDark ? 'icon-sun' : 'icon-moon';
        var el = document.querySelector('#layout-mode-login');
        if (el) el.innerHTML = '<i class="ficon feather ' + iconClass + '"></i>';
    }

    function applyLayout(layout) {
        var body = document.getElementById('content_body');
        if (layout === 'light') {
            body.dataset.type = 'light';
            body.classList.remove('dark-layout');
            body.classList.add('light-mode');
        } else {
            body.dataset.type = 'dark';
            body.classList.add('dark-layout');
            body.classList.remove('light-mode');
        }
        updateLoginThemeIcon();
    }

    var stored = localStorage.getItem('judge_currentLayout');
    if (!stored) {
        stored = 'dark';
        localStorage.setItem('judge_currentLayout', 'dark');
    }
    applyLayout(stored);

    document.getElementById('layout-mode-login').addEventListener('click', function (e) {
        e.preventDefault();
        var next = localStorage.getItem('judge_currentLayout') === 'light' ? 'dark' : 'light';
        localStorage.setItem('judge_currentLayout', next);
        applyLayout(next);
    });

    document.querySelector('.toggle-password')?.addEventListener('click', function () {
        var input = document.getElementById('password');
        var icon = document.getElementById('toggle-password-icon');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // Subtle star field
    var canvas = document.getElementById('stars-canvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var stars = [];
        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        function initStars() {
            stars = [];
            var count = Math.min(120, Math.floor((canvas.width * canvas.height) / 12000));
            for (var i = 0; i < count; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.4 + 0.3,
                    a: Math.random() * 0.6 + 0.2,
                    s: Math.random() * 0.02 + 0.005
                });
            }
        }
        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            stars.forEach(function (star) {
                star.a += star.s;
                if (star.a > 0.85 || star.a < 0.15) star.s *= -1;
                ctx.beginPath();
                ctx.fillStyle = 'rgba(196, 163, 90,' + star.a + ')';
                ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
                ctx.fill();
            });
            requestAnimationFrame(draw);
        }
        resize();
        initStars();
        draw();
        window.addEventListener('resize', function () {
            resize();
            initStars();
        });
    }
})();
</script>
</body>
</html>
