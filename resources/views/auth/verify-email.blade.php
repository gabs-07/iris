<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifica tu correo | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="auth-body">
  <main class="login-wrapper">
    <section class="login-container auth-simple-container">
      <div class="auth-simple-card">
        <span class="branding-badge">Seguridad de cuenta</span>
        <h1>Verifica tu correo electrónico</h1>
        <p>Enviamos un enlace de verificación a <strong>{{ auth()->user()->email }}</strong>. Debes verificarlo antes de usar IRIS.</p>
        @include('partials.frontend-alerts')
        <p class="input-hint">Revisa tu bandeja de entrada o solicita un nuevo enlace de verificación.</p>
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button type="submit" class="btn-primary btn-full">Reenviar correo de verificación</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn-outline btn-full">Cerrar sesión</button></form>
      </div>
    </section>
  </main>
</body>
</html>
