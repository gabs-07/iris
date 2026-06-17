<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auxilio invitado | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body">
<main class="dashboard-main auxilio-main" style="max-width:980px;margin:0 auto;">
  <header class="dashboard-header">
    <div class="welcome-text">
      <h1>Auxilio emocional para invitado</h1>
      <p>Conecta con un profesional en Modo Escucha sin crear cuenta. Al terminar, IRIS te invita a registrarte para guardar seguimiento.</p>
    </div>
    <div class="header-actions"><a class="btn-outline" href="{{ url('/') }}">Volver al inicio</a><a class="btn-primary" href="{{ route('login') }}">Iniciar sesión</a></div>
  </header>

  @include('partials.frontend-alerts')

  <section class="dashboard-section-grid auxilio-grid-enhanced">
    <article class="dash-card auxilio-card-highlight">
      <span class="backend-chip">Sin cuenta</span>
      <h2>Solicitar Auxilio ahora</h2>
      <p>IRIS buscará automáticamente a un psicólogo o psiquiatra aprobado con Modo Escucha activo. Si hay disponibilidad, se creará una reunión Zoom.</p>

      @if($activeAuxilio)
        <div class="auxilio-connected-box">
          <strong>Profesional conectado</strong>
          <span>{{ $activeAuxilio->professional?->nombre_completo ?? 'Especialista IRIS' }}</span>
          <small>Folio: {{ $activeAuxilio->folio }} · Disponible hasta {{ optional($activeAuxilio->session_access_ends_at)->format('H:i') }}</small>
        </div>
        @if($activeAuxilio->patient_video_url)
          <a class="btn-primary btn-lg" href="{{ $activeAuxilio->patient_video_url }}" target="_blank" rel="noopener">Entrar a sesión de auxilio por Zoom</a>
        @endif
        <form method="POST" action="{{ route('guest.auxilio.finish') }}" class="auxilio-request-form" style="margin-top:18px;">
          @csrf
          <button class="btn-outline" type="submit">Terminé, quiero registrarme</button>
        </form>
      @else
        <form method="POST" action="{{ route('guest.auxilio.request') }}" class="auxilio-request-form">
          @csrf
          <label class="backend-field">Nombre opcional
            <input name="guest_name" maxlength="120" value="{{ old('guest_name') }}" placeholder="Ej. Invitado">
          </label>
          <label class="backend-field">Teléfono o correo opcional
            <input name="guest_contact" maxlength="120" value="{{ old('guest_contact') }}" placeholder="Solo si quieres que el profesional tenga un contacto de respaldo">
          </label>
          <label class="backend-check">
            <input type="checkbox" name="acepta_aviso_auxilio" value="1" required>
            <span>Entiendo que Auxilio es contención emocional inicial y no sustituye el 911, urgencias médicas ni atención psiquiátrica de emergencia.</span>
          </label>
          <button class="btn-primary btn-lg" type="submit">Buscar profesional en Modo Escucha</button>
        </form>
      @endif
    </article>

    <aside class="dash-card auxilio-side-card">
      <h3>Después de la sesión</h3>
      <ul>
        <li>Regístrate como paciente para guardar diario emocional y citas.</li>
        <li>Podrás seleccionar un especialista y autorizar acceso a tu diario.</li>
        <li>Si hay riesgo inmediato para tu vida o integridad, contacta servicios de emergencia.</li>
      </ul>
      <a class="btn-primary" href="{{ route('register') }}">Crear cuenta de paciente</a>
    </aside>
  </section>
</main>
</body>
</html>
