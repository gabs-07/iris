<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat profesional | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div><script src="{{ asset('js/menu-psicologo.js') }}"></script>
<main class="dashboard-main">
  @include('partials.profesional-header', ['title' => 'Chat profesional', 'subtitle' => 'Canal privado para psicólogos y psiquiatras. La conversación se organiza con tags.'])
  @include('partials.frontend-alerts')

  <section class="dash-card">
    <div class="card-top">
      <div><span class="backend-chip">Solo profesionales</span><h2>Nuevo mensaje</h2><p class="card-subtitle">Selecciona tags para clasificar el mensaje. No compartas datos identificables innecesarios de pacientes.</p></div>
    </div>
    <form method="POST" action="{{ route('profesional.chat-profesional.store') }}" class="form-grid" style="grid-template-columns:1fr;">
      @csrf
      <textarea name="message" rows="4" required maxlength="4000" placeholder="Escribe el mensaje para psicólogos/psiquiatras..."></textarea>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($availableTags as $tag)
          <label class="backend-chip" style="cursor:pointer;"><input type="checkbox" name="tags[]" value="{{ $tag }}" style="margin-right:6px;">#{{ $tag }}</label>
        @endforeach
      </div>
      <button class="btn-primary" type="submit">Enviar al chat profesional</button>
    </form>
  </section>

  <section class="dash-card">
    <div class="card-top"><div><h2>Conversación</h2><p class="card-subtitle">Mensajes recientes del equipo profesional.</p></div></div>
    <div style="display:grid;gap:14px;">
      @forelse($messages as $message)
        <article class="dash-card" style="box-shadow:none;border:1px solid #e5e7eb;">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div><strong>{{ $message->user?->nombre_completo ?? 'Profesional IRIS' }}</strong><br><small>{{ ucfirst($message->user?->rol ?? 'profesional') }} · {{ $message->created_at?->format('d/m/Y H:i') }}</small></div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              @foreach(($message->tags ?? ['general']) as $tag)<span class="backend-chip">#{{ $tag }}</span>@endforeach
            </div>
          </div>
          <p style="white-space:pre-wrap;margin-top:12px;">{{ $message->message }}</p>
        </article>
      @empty
        <div class="backend-empty">Aún no hay mensajes en el chat profesional.</div>
      @endforelse
    </div>
  </section>
</main>
</body>
</html>
