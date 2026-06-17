<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/header.css') }}">
  <link rel="stylesheet" href="{{ asset('css/recuperar.css') }}">
</head>
<body class="auth-body">
<header class="main-header" id="mainHeader">
  <a href="{{ url('/') }}" class="logo iris-brand-logo"><img src="{{ asset('img/iris-logo.png') }}" alt="IRIS" class="brand-logo-image"></a>
  <div class="header-actions"><a href="{{ url('/registro') }}" class="btn-nav-login">Crear cuenta</a><a href="{{ url('/login') }}" class="btn-nav-register">Iniciar sesión</a></div>
</header>
<main class="recovery-wrapper">
  <div class="recovery-container animate-fade-up">
    <div class="recovery-header">
      <div class="icon-wrapper">🔒</div>
      <h1>¿Olvidaste tu contraseña?</h1>
      <p>Ingresa tu correo electrónico y te enviaremos un enlace real para restablecer tu contraseña.</p>
    </div>

    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <form class="recovery-form" action="{{ route('password.email') }}" method="post">
      @csrf
      <div class="input-group">
        <label for="recovery-email">Correo electrónico</label>
        <div class="input-wrapper">
          <input type="email" id="recovery-email" name="email" value="{{ old('email') }}" placeholder="correo@dominio.com" autocomplete="email" required>
        </div>
        <p class="input-hint">El enlace se enviará usando el correo SMTP configurado en Laravel.</p>
      </div>
      <button type="submit" class="btn-primary btn-full">Enviar enlace de recuperación</button>
    </form>
    <div class="recovery-footer"><a href="{{ url('/login') }}" class="back-to-login">← Volver al inicio de sesión</a></div>
  </div>
</main>
</body>
</html>
