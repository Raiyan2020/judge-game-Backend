<footer class="footer footer-static admin-dashboard-footer">
    <p class="admin-dashboard-footer__text mb-0 text-center">
        {{ __('All rights reserved') }} — JUDGE GAME
    </p>
</footer>

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

@include('dashboard.layout.scripts')
@stack('scripts')

<script>
    $(window).on('load', function () {
        $('.loader').fadeOut();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const savedColor = localStorage.getItem('theme-primary-color');
        const savedHover = localStorage.getItem('theme-primary-hover');

        const hexToRgb = hex => {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `${r}, ${g}, ${b}`;
        };

        const relativeLuminance = hex => {
            const channels = [hex.slice(1, 3), hex.slice(3, 5), hex.slice(5, 7)].map(value => {
                const channel = parseInt(value, 16) / 255;
                return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
            });
            return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
        };

        const applyThemeColors = function (color, hover) {
            document.documentElement.style.setProperty('--primary', color);
            document.documentElement.style.setProperty('--primary-hover', hover);
            document.documentElement.style.setProperty('--primary-rgb', hexToRgb(color));

            const isDarkAccent = relativeLuminance(color) < 0.24;
            document.documentElement.classList.toggle('theme-accent-dark', isDarkAccent);

            if (isDarkAccent) {
                document.documentElement.style.setProperty('--primary-ui', '#c4a35a');
                document.documentElement.style.setProperty('--primary-ui-hover', '#a8893e');
                document.documentElement.style.setProperty('--primary-ui-rgb', '196, 163, 90');
            } else {
                document.documentElement.style.setProperty('--primary-ui', color);
                document.documentElement.style.setProperty('--primary-ui-hover', hover);
                document.documentElement.style.setProperty('--primary-ui-rgb', hexToRgb(color));
            }
        };

        const colorCircles = document.querySelectorAll('.theme-color-circle');
        const defaultColor = '#c4a35a';
        const defaultHover = '#a8893e';

        const setActiveCircle = function (color) {
            colorCircles.forEach(function (circle) {
                const isActive = circle.getAttribute('data-color') &&
                    circle.getAttribute('data-color').toLowerCase() === (color || '').toLowerCase();
                circle.classList.toggle('active', isActive);
            });
        };

        if (savedColor && savedHover) {
            applyThemeColors(savedColor, savedHover);
            setActiveCircle(savedColor);
        } else {
            applyThemeColors(defaultColor, defaultHover);
            localStorage.setItem('theme-primary-color', defaultColor);
            localStorage.setItem('theme-primary-hover', defaultHover);
            setActiveCircle(defaultColor);
        }

        colorCircles.forEach(function (circle) {
            circle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const color = this.getAttribute('data-color');
                const hover = this.getAttribute('data-hover');

                applyThemeColors(color, hover);
                localStorage.setItem('theme-primary-color', color);
                localStorage.setItem('theme-primary-hover', hover);
                setActiveCircle(color);
            });
        });

        $('table.dataTable').addClass('admin-data-table');
    });
</script>

<script>
(function () {
    var canvas = document.getElementById('dash-premium-canvas');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var particles = [];
    var rafId = null;

    function accentRgb() {
        return getComputedStyle(document.documentElement).getPropertyValue('--primary-rgb').trim() || '196, 163, 90';
    }

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    function initParticles() {
        particles = [];
        var count = Math.min(70, Math.floor((canvas.width * canvas.height) / 22000));
        for (var i = 0; i < count; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.12,
                vy: (Math.random() - 0.5) * 0.12,
                r: Math.random() * 1.1 + 0.35,
                pulse: Math.random() * Math.PI * 2
            });
        }
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        var rgb = accentRgb();
        var linkDistance = 130;

        for (var i = 0; i < particles.length; i++) {
            for (var j = i + 1; j < particles.length; j++) {
                var dx = particles[i].x - particles[j].x;
                var dy = particles[i].y - particles[j].y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < linkDistance) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(' + rgb + ',' + (0.07 * (1 - dist / linkDistance)) + ')';
                    ctx.lineWidth = 0.55;
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }

        particles.forEach(function (p) {
            p.x += p.vx;
            p.y += p.vy;
            p.pulse += 0.012;

            if (p.x <= 0 || p.x >= canvas.width) p.vx *= -1;
            if (p.y <= 0 || p.y >= canvas.height) p.vy *= -1;

            var alpha = 0.18 + (Math.sin(p.pulse) + 1) * 0.22;
            ctx.beginPath();
            ctx.fillStyle = 'rgba(' + rgb + ',' + alpha + ')';
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();
        });

        rafId = requestAnimationFrame(draw);
    }

    function start() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        resize();
        initParticles();
        if (rafId) cancelAnimationFrame(rafId);
        draw();
    }

    start();
    window.addEventListener('resize', start);
})();
</script>
</body>
</html>
