<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
</head>
<body class="auth-body register-body">
<header class="main-header" id="mainHeader">
    <a href="{{ url('/') }}" class="logo iris-brand-logo"><img src="{{ asset('img/iris-logo.png') }}" alt="IRIS" class="brand-logo-image"></a>
    <div class="header-actions">
        <a href="{{ url('/login') }}" class="btn-nav-login">Iniciar sesión</a>
        <a href="{{ url('/registro') }}" class="btn-nav-register">Crear cuenta</a>
    </div>
    <button class="hamburger" id="hamburgerBtn" aria-label="Menú" aria-expanded="false"><span></span><span></span><span></span></button>
</header>
<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <div class="mobile-actions"><a href="{{ url('/login') }}" class="btn-primary">Iniciar sesión</a><a href="{{ url('/registro') }}" class="btn-outline">Crear cuenta</a></div>
</div>

<main class="register-wrapper">
    <div class="register-container animate-fade-up">
        <section class="register-form-section">
            <div class="form-header">
                <h1>Crea tu cuenta</h1>
                <p>El registro inicial solo pide datos esenciales. Si eres psicólogo/a o psiquiatra, completarás tu perfil profesional después de entrar y el administrador deberá autorizarlo.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom:18px; padding:14px; border-radius:14px; background:#fff1f2; color:#9f1239;">
                    <strong>Revisa los campos marcados.</strong>
                    <ul style="margin:8px 0 0 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success" style="margin-bottom:18px; padding:14px; border-radius:14px; background:#e0f2e9; color:#1e5a45;">{{ session('success') }}</div>
            @endif

            <form class="register-form" id="registerForm" action="{{ url('/registro') }}" method="post" novalidate>
                @csrf

                <fieldset class="form-section">
                    <legend>Tipo de cuenta</legend>
                    <div class="role-selector" role="radiogroup" aria-label="Tipo de cuenta">
                        <input type="radio" name="rol" id="rol-paciente" value="paciente" {{ old('rol', 'paciente') === 'paciente' ? 'checked' : '' }}>
                        <label class="role-option" for="rol-paciente"><span class="role-icon">👤</span>Soy Paciente</label>

                        <input type="radio" name="rol" id="rol-psicologo" value="psicologo" {{ old('rol') === 'psicologo' ? 'checked' : '' }}>
                        <label class="role-option" for="rol-psicologo"><span class="role-icon">🧠</span>Soy Psicólogo/a</label>

                        <input type="radio" name="rol" id="rol-psiquiatra" value="psiquiatra" {{ old('rol') === 'psiquiatra' ? 'checked' : '' }}>
                        <label class="role-option" for="rol-psiquiatra"><span class="role-icon">⚕️</span>Soy Psiquiatra</label>

                        <input type="radio" name="rol" id="rol-doctor-interno" value="doctor_interno" {{ old('rol') === 'doctor_interno' ? 'checked' : '' }}>
                        <label class="role-option" for="rol-doctor-interno"><span class="role-icon">🩺</span>Soy Doctor interno</label>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Datos personales</legend>
                    <div class="form-grid col-2">
                        <div class="input-group">
                            <label for="nombre">Nombre(s) <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre(s)" required autocomplete="given-name">
                        </div>
                        <div class="input-group">
                            <label for="apellidos">Apellidos <span class="required">*</span></label>
                            <input type="text" id="apellidos" name="apellidos" value="{{ old('apellidos') }}" placeholder="Apellidos" required autocomplete="family-name">
                        </div>
                        <div class="input-group">
                            <label for="fecha_nacimiento">Fecha de nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}">
                        </div>
                        <div class="input-group">
                            <label for="genero">Género</label>
                            <select id="genero" name="genero">
                                <option value="">Seleccionar…</option>
                                @foreach (['femenino' => 'Femenino', 'masculino' => 'Masculino', 'no-binario' => 'No binario', 'prefiero-no-decir' => 'Prefiero no decirlo', 'otro' => 'Otro'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('genero') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-span-2">
                            <label for="telefono">Teléfono celular <span class="required">*</span></label>
                            <div class="input-with-prefix"><span class="input-prefix">+52</span><input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="55 1234 5678" required autocomplete="tel"></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Datos de cuenta</legend>
                    <div class="form-grid col-2">
                        <div class="input-group col-span-2">
                            <label for="email">Correo electrónico <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="correo@dominio.com" required autocomplete="email">
                        </div>
                        <div class="input-group">
                            <label for="password">Contraseña <span class="required">*</span></label>
                            <div class="input-wrapper"><input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Mostrar contraseña">👁</button></div>
                            <div class="password-strength" id="password-strength"></div>
                        </div>
                        <div class="input-group">
                            <label for="password_confirmation">Confirmar contraseña <span class="required">*</span></label>
                            <div class="input-wrapper"><input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repite tu contraseña" required autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Mostrar contraseña">👁</button></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Contacto de emergencia</legend>
                    <div class="form-grid col-2">
                        <div class="input-group">
                            <label for="emergencia_nombre">Nombre completo <span class="required">*</span></label>
                            <input type="text" id="emergencia_nombre" name="emergencia_nombre" value="{{ old('emergencia_nombre') }}" placeholder="Nombre del contacto" required>
                        </div>
                        <div class="input-group">
                            <label for="emergencia_relacion">Relación <span class="required">*</span></label>
                            <select id="emergencia_relacion" name="emergencia_relacion" required>
                                <option value="">Seleccionar…</option>
                                @foreach (['madre' => 'Madre', 'padre' => 'Padre', 'hermano/a' => 'Hermano/a', 'pareja' => 'Pareja', 'amigo/a' => 'Amigo/a', 'otro' => 'Otro'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('emergencia_relacion') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-span-2">
                            <label for="emergencia_telefono">Teléfono <span class="required">*</span></label>
                            <div class="input-with-prefix"><span class="input-prefix">+52</span><input type="tel" id="emergencia_telefono" name="emergencia_telefono" value="{{ old('emergencia_telefono') }}" placeholder="55 1234 5678" required></div>
                        </div>
                    </div>
                </fieldset>

                <div class="terms-section">
                    <h3>Acuerdos para aceptar</h3>
                    <label class="checkbox-label"><input type="checkbox" name="acepta_terminos" required {{ old('acepta_terminos') ? 'checked' : '' }}><span class="checkmark"></span>Acepto los <a href="{{ url('/legal/view/terminos_condiciones') }}" target="_blank">Términos y Condiciones</a> <span class="required">*</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="acepta_privacidad" required {{ old('acepta_privacidad') ? 'checked' : '' }}><span class="checkmark"></span>Acepto el <a href="{{ url('/legal/view/aviso_privacidad') }}" target="_blank">Aviso de Privacidad</a> <span class="required">*</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="acepta_datos_sensibles" required {{ old('acepta_datos_sensibles') ? 'checked' : '' }}><span class="checkmark"></span>Acepto el <a href="{{ url('/legal/view/consentimiento_datos_sensibles') }}" target="_blank">tratamiento de datos personales sensibles</a> conforme al aviso de privacidad <span class="required">*</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="acepta_comunicaciones" {{ old('acepta_comunicaciones') ? 'checked' : '' }}><span class="checkmark"></span>Acepto el <a href="{{ url('/legal/view/consentimiento_comunicaciones') }}" target="_blank">consentimiento de comunicaciones</a> para recibir avisos y frases motivacionales de IRIS</label>
                    <div id="professional-agreements" class="psi-notice" style="display:none; margin-top:14px;">
                        <label class="checkbox-label"><input type="checkbox" name="acepta_condiciones_profesionales" {{ old('acepta_condiciones_profesionales') ? 'checked' : '' }}><span class="checkmark"></span>Acepto el <a href="{{ url('/legal/view/condiciones_profesionales') }}" target="_blank">Código de Ética y Condiciones Profesionales</a></label>
                        <label class="checkbox-label"><input type="checkbox" name="declara_veracidad_profesional" {{ old('declara_veracidad_profesional') ? 'checked' : '' }}><span class="checkmark"></span>Declaro que mi información profesional será verdadera y acepto que el administrador autorice mi perfil antes de atender pacientes, conforme a las condiciones profesionales</label>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full btn-lg">Crear cuenta</button>
            </form>
            <p class="form-footer">¿Ya tienes cuenta? <a href="{{ url('/login') }}">Inicia sesión aquí</a></p>
        </section>

        <aside class="register-branding">
            <div class="branding-badge">Plataforma IRIS</div>
            <h2>Tu bienestar<br>comienza <em>aquí</em></h2>
            <p>Registro claro, mínimo y listo para integrarse con perfiles completos dentro del sistema.</p>
            <div class="branding-features">
                <div class="feature-set" style="display:flex">
                    <div class="feature-item"><div class="feature-icon">🔒</div><div><strong>Privacidad</strong><span>Consentimientos legales desde el registro.</span></div></div>
                    <div class="feature-item"><div class="feature-icon">🧾</div><div><strong>Perfil profesional validado</strong><span>Psicólogos y psiquiatras requieren autorización del admin.</span></div></div>
                    <div class="feature-item"><div class="feature-icon">📅</div><div><strong>Agenda integrada</strong><span>Citas, pagos, diario, tareas y comunidad.</span></div></div>
                </div>
            </div>
        </aside>
    </div>
</main>

<script>
(function () {
    const header = document.getElementById('mainHeader');
    const mobileMenuEl = document.getElementById('mobileMenu');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    let lastY = 0, ticking = false;
    function closeMobileMenu() { hamburgerBtn?.classList.remove('open'); mobileMenuEl?.classList.remove('open'); hamburgerBtn?.setAttribute('aria-expanded', 'false'); mobileMenuEl?.setAttribute('aria-hidden', 'true'); }
    function onScroll() { const y = window.scrollY; const down = y > lastY; if (y > 80 && down) { header?.classList.add('header-hidden'); closeMobileMenu(); } else header?.classList.remove('header-hidden'); header?.classList.toggle('scrolled', y > 10); lastY = y; ticking = false; }
    window.addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(onScroll); ticking = true; } }, { passive: true });
    hamburgerBtn?.addEventListener('click', function () { const isOpen = mobileMenuEl.classList.toggle('open'); hamburgerBtn.classList.toggle('open'); hamburgerBtn.setAttribute('aria-expanded', isOpen); mobileMenuEl.setAttribute('aria-hidden', !isOpen); });

    const professionalBox = document.getElementById('professional-agreements');
    const professionalChecks = professionalBox?.querySelectorAll('input[type="checkbox"]') || [];
    function syncRole() {
        const role = document.querySelector('input[name="rol"]:checked')?.value || 'paciente';
        const isProfessional = ['psicologo', 'psiquiatra', 'doctor_interno'].includes(role);
        professionalBox.style.display = isProfessional ? 'block' : 'none';
        professionalChecks.forEach(check => check.required = isProfessional);
    }
    document.querySelectorAll('input[name="rol"]').forEach(input => input.addEventListener('change', syncRole));
    syncRole();

    document.querySelectorAll('.toggle-password').forEach(btn => btn.addEventListener('click', function () {
        const input = this.closest('.input-wrapper').querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
    }));
    const password = document.getElementById('password');
    const bar = document.getElementById('password-strength');
    password?.addEventListener('input', function () {
        let score = 0; const val = this.value;
        if (val.length >= 8) score++; if (/[A-Z]/.test(val)) score++; if (/[0-9]/.test(val)) score++; if (/[^A-Za-z0-9]/.test(val)) score++;
        bar.className = 'password-strength'; bar.textContent = val ? ['Muy débil','Débil','Regular','Fuerte'][Math.max(score-1,0)] : '';
        if (val) bar.classList.add(['strength-1','strength-2','strength-3','strength-4'][Math.max(score-1,0)]);
    });
})();
</script>
</body>
</html>
