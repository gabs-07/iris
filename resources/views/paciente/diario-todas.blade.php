<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Todas mis entradas | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/diario.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body diary-all-page">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>
<main class="dashboard-main">
  @include('partials.paciente-header', ['title' => 'Todas tus entradas', 'subtitle' => 'Consulta el historial completo de tu diario personal.'])
  @include('partials.frontend-alerts')

  <section class="dash-card diary-history-card diary-all-card">
    <div class="card-top diary-card-header-actions">
      <div>
        <h2>Historial completo</h2>
        <p class="card-subtitle">La búsqueda filtra por título, contenido, emoción y fecha.</p>
      </div>
      <a class="btn-outline btn-sm" href="{{ route('paciente.diario') }}">Volver al diario</a>
    </div>
    <div class="diary-summary"><span>Entradas guardadas</span><strong>{{ $diaryEntries->count() }}</strong></div>
    <div class="diary-search-container">
      <svg class="diary-search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <input type="search" class="diary-search-input" id="diary-filter" placeholder="Buscar entradas, emociones o palabras clave..." autocomplete="off">
    </div>
    <div class="diary-entries diary-entries-all" id="diary-entries">
      @forelse($diaryEntries as $entry)
        <article class="diary-entry" data-search="{{ e(($entry['title'] ?? '').' '.($entry['mood'] ?? '').' '.($entry['content'] ?? '').' '.($entry['date'] ?? '')) }}">
          <div class="diary-entry-top">
            <div><span class="diary-entry-date">{{ $entry['date'] }}</span><h3>{{ $entry['title'] ?: 'Entrada del diario' }}</h3></div>
            <span class="diary-entry-emoji">{{ $entry['emoji'] ?: '📝' }}</span>
          </div>
          @if(!empty($entry['notes']))
            <div class="diary-entry-text" style="display:grid;gap:10px;">
              @foreach($entry['notes'] as $note)
                <div style="border-left:3px solid #2c7a5e;padding-left:10px;">
                  <strong>{{ $note['time'] ?? '--:--' }}</strong>
                  @if(!empty($note['emoji'])) <span>{{ $note['emoji'] }}</span> @endif
                  @if(!empty($note['mood'])) <span class="backend-chip">{{ $note['mood'] }}</span> @endif
                  @if(!empty($note['title']))<p><strong>{{ $note['title'] }}</strong></p>@endif
                  <p>{{ $note['content'] ?? '' }}</p>
                </div>
              @endforeach
            </div>
          @else
            <p class="diary-entry-text">{{ $entry['content'] }}</p>
          @endif
          @if($entry['mood'])<span class="backend-chip">{{ $entry['mood'] }}</span>@endif
          @if(!empty($entry['authorized_professional']))<span class="backend-chip">Compartido con {{ $entry['authorized_professional'] }}</span>@endif
        </article>
      @empty
        <div class="diary-empty">No has escrito entradas todavía.</div>
      @endforelse
    </div>
    <div class="backend-empty" id="diary-no-results" hidden>No encontramos entradas con ese texto.</div>
  </section>
</main>
@include('partials.floating-auxilio')
<script>
(function(){
  const input = document.getElementById('diary-filter');
  const entries = Array.from(document.querySelectorAll('.diary-entry'));
  const empty = document.getElementById('diary-no-results');
  const normalize = (value) => (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  function applyFilter() {
    const query = normalize(input?.value);
    let visible = 0;
    entries.forEach(entry => {
      const show = !query || normalize(entry.dataset.search).includes(query);
      entry.hidden = !show;
      if (show) visible++;
    });
    if (empty) empty.hidden = query === '' || visible > 0;
  }
  input?.addEventListener('input', applyFilter);
  applyFilter();
})();
</script>
</body>
</html>
