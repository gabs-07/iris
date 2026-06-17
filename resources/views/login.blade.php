<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="auth-body">

    <!-- ═══════════════════════════════════════════════
         HEADER PRINCIPAL (se oculta al bajar scroll)
    ═══════════════════════════════════════════════ -->
    <header class="main-header" id="mainHeader">
        <a href="{{ url('/') }}" class="logo iris-brand-logo"><img src="{{ asset('img/iris-logo.png') }}" alt="IRIS" class="brand-logo-image"></a>


        <div class="header-actions">
            <a href="{{ url('/registro') }}" class="btn-nav-login">Crear cuenta</a>
            <a href="{{ url('/login') }}" class="btn-nav-register">Iniciar sesión</a>
        </div>

        <button class="hamburger" id="hamburgerBtn" aria-label="Menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </header>

    <!-- Menú Mobile -->
    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">

        <div class="mobile-actions">
            <a href="{{ url('/login') }}" class="btn-primary">Iniciar sesión</a>
            <a href="{{ url('/registro') }}" class="btn-outline">Crear cuenta</a>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ═══════════════════════════════════════════════ -->
    <main class="login-wrapper">
        <div class="login-container animate-fade-up">

            <!-- Columna Branding -->
            <aside class="login-branding">
                <div class="branding-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Plataforma certificada
                </div>
                <h2>Tu bienestar mental, <em>siempre contigo</em></h2>
                <p>Conectamos a personas con psicólogos certificados para acompañarte en cada etapa de tu vida.</p>
                <div class="branding-stats">
                    <div class="stat-item">
                        <strong>Verificación</strong>
                        <span>Perfiles profesionales revisados por administración</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <strong>Privacidad</strong>
                        <span>Datos clínicos protegidos y auditados</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <strong>PayPal</strong>
                        <span>Pagos confirmados antes de agendar</span>
                    </div>
                </div>
                <div class="branding-testimonial">
                    <p>Accede con tu cuenta verificada para gestionar citas, tareas, comunidad y seguimiento clínico.</p>
                </div>
            </aside>

            <!-- Columna Formulario -->
            <section class="login-form-section">

                @if ($errors->any())
                    <div style="margin-bottom:18px; padding:14px; border-radius:14px; background:#fff1f2; color:#9f1239;">{{ $errors->first() }}</div>
                @endif
                @if (session('success'))
                    <div style="margin-bottom:18px; padding:14px; border-radius:14px; background:#e0f2e9; color:#1e5a45;">{{ session('success') }}</div>
                @endif
                <div class="form-header">
                    <h1>Bienvenido de vuelta</h1>
                    <p>Ingresa tus credenciales para continuar tu proceso.</p>
                </div>

                <form class="login-form" action="{{ url('/login') }}" method="post" novalidate>
                    @csrf

                    <!-- Email -->
                    <div class="input-group">
                        <label for="email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <input type="email" id="email" name="email" placeholder="correo@dominio.com" autocomplete="email" required>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="input-group">
                        <div class="label-row">
                            <label for="password">Contraseña</label>
                            <a href="{{ url('/recuperar') }}" class="forgot-password">¿Olvidaste tu contraseña?</a>
                        </div>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="password" name="password" placeholder="Tu contraseña" autocomplete="current-password" required>
                            <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Recordarme -->
                    <div class="remember-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Mantener sesión iniciada
                        </label>
                    </div>

                    <button type="submit" class="btn-primary btn-full">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Iniciar sesión
                    </button>

                    <!-- Separador -->
                    <div class="divider"><span></span></div>

                    <p class="form-footer">
                        ¿No tienes cuenta? <a href="{{ url('/registro') }}">Regístrate gratis</a>
                    </p>
                    <p class="form-footer" style="margin-top:10px; font-size:.86rem; line-height:1.5;">
                        Al usar IRIS puedes consultar el <a href="{{ url('/legal/view/aviso_privacidad') }}" target="_blank">Aviso de Privacidad</a>,
                        los <a href="{{ url('/legal/view/terminos_condiciones') }}" target="_blank">Términos y Condiciones</a>
                        y el <a href="{{ url('/legal') }}" target="_blank">Centro Legal</a>.
                    </p>
                </form>
            </section>
        </div>
    </main>

    <!-- ═══════════════════════════════════════════════
         SCRIPTS
    ═══════════════════════════════════════════════ -->
    <script>
        /* ── Scroll-hide header ─────────────────────────── */
        (function () {
            const header = document.getElementById('mainHeader');
            const mobileMenu = document.getElementById('mobileMenu');
            let lastY = 0;
            let ticking = false;

            function onScroll() {
                const currentY = window.scrollY;
                const isScrollingDown = currentY > lastY;
                const isPastThreshold = currentY > 80;

                if (isPastThreshold && isScrollingDown) {
                    header.classList.add('header-hidden');
                    // Cerrar menú móvil si está abierto
                    if (mobileMenu.classList.contains('open')) {
                        closeMobileMenu();
                    }
                } else {
                    header.classList.remove('header-hidden');
                }

                // Fondo glassmorphism al hacer scroll
                if (currentY > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }

                lastY = currentY;
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    requestAnimationFrame(onScroll);
                    ticking = true;
                }
            }, { passive: true });
        })();

        /* ── Hamburger / menú móvil ─────────────────────── */
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        function closeMobileMenu() {
            hamburgerBtn.classList.remove('open');
            mobileMenu.classList.remove('open');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
        }

        hamburgerBtn.addEventListener('click', function () {
            const isOpen = mobileMenu.classList.toggle('open');
            hamburgerBtn.classList.toggle('open');
            hamburgerBtn.setAttribute('aria-expanded', isOpen);
            mobileMenu.setAttribute('aria-hidden', !isOpen);
        });

        /* ── Toggle visibilidad de contraseña ───────────── */
        document.querySelectorAll('.toggle-password').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = this.closest('.input-wrapper').querySelector('input');
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                this.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });
    </script>
</body>
</html>
