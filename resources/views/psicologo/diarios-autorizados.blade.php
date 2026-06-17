<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diarios autorizados | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/diario.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div><script src="{{ asset('js/menu-psicologo.js') }}"></script>
<main class="dashboard-main">
  @include('partials.profesional-header', ['title' => 'Diarios autorizados', 'subtitle' => 'Solo aparecen registros donde el paciente te autorizó explícitamente.'])
  @include('partials.frontend-alerts')

  <section class="dash-card diary-history-card diary-all-card">
    <div class="card-top"><div><span class="backend-chip">Permiso clínico</span><h2>Registros compartidos contigo</h2><p class="card-subtitle">Cada día agrupa varias notas con su hora exacta.</p></div></div>
    <div class="diary-entries diary-entries-all">
      @forelse($diaryEntries as $entry)
        <article class="diary-entry">
          <div class="diary-entry-top">
            <div>
              <span class="diary-entry-date">{{ optional($entry->entry_date)->format('Y-m-d') }}</span>
              <h3>{{ $entry->patient?->nombre_completo ?? 'Paciente IRIS' }}</h3>
              <small>Autorizado {{ optional($entry->authorized_at)->format('d/m/Y H:i') }}</small>
            </div>
            <span class="diary-entry-emoji">{{ $entry->emoji ?: '📝' }}</span>
          </div>
          <p class="diary-entry-text" style="white-space:pre-wrap;">{{ $entry->content }}</p>
          @if($entry->mood)<span class="backend-chip">{{ $entry->mood }}</span>@endif
        </article>
      @empty
        <div class="diary-empty">Todavía no tienes diarios autorizados por pacientes.</div>
      @endforelse
    </div>
    <div style="margin-top:16px;">{{ $diaryEntries->links() }}</div>
  </section>
</main>
</body>
</html>
