<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buscar especialista | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
  <link rel="stylesheet" href="{{ asset('css/directorio.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body directory-page">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>
<main class="dashboard-main">
  @include('partials.paciente-header', ['title'=>'Buscar especialista', 'subtitle'=>'Elige un profesional aprobado para tu primera cita. Después podrás agendar seguimientos desde Mis citas.'])
  @include('partials.frontend-alerts')

  <section class="directory-search-panel">
    <div class="search-filters-container backend-directory-search">
      <div class="filter-group directory-search-box">
        <span class="icon" aria-hidden="true">⌕</span>
        <input class="filter-input" id="specialist-filter" type="search" placeholder="Buscar por nombre, especialidad, modalidad, idioma o enfoque..." autocomplete="off">
      </div>
      <select class="filter-select" id="specialist-role-filter" aria-label="Filtrar por tipo de especialista">
        <option value="">Todos</option>
        <option value="psicologo">Psicólogos</option>
        <option value="psiquiatra">Psiquiatras</option>
      </select>
    </div>
  </section>

  <section class="specialists-grid backend-specialists-grid" id="specialists-grid">
    @forelse($professionals as $professional)
      @php
        $profile = $professional->professionalProfile;
        $amount = (float) ($profile?->costo_min ?: 0);
        $formations = collect($profile?->formacion_academica ?: [])->filter()->values();
        $specialties = collect($profile?->especialidades ?: $profile?->areas ?: [])->filter()->values();
        $careDays = collect($profile?->dias_atencion ?: [])->filter()->values();
        $dayMap = ['lunes'=>'L','martes'=>'M','miercoles'=>'M','miércoles'=>'M','jueves'=>'J','viernes'=>'V','sabado'=>'S','sábado'=>'S','domingo'=>'D'];
        $searchText = implode(' ', array_merge([
          $professional->nombre_completo,
          $professional->rol,
          $profile?->especialidad_principal,
          $profile?->modalidad,
          $profile?->idiomas,
          $profile?->biografia,
          $profile?->servicios,
          $profile?->presentacion,
          $profile?->ubicacion,
        ], $specialties->all(), $formations->all(), (array)($profile?->enfoques ?: [])));
      @endphp
      <article class="specialist-card backend-specialist-card" data-role="{{ $professional->rol }}" data-search="{{ e(
          \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($searchText))
      ) }}">
        <div class="specialist-header backend-specialist-header">
          <div class="specialist-avatar avatar-lg">{{ $professional->initials() }}</div>
          <div class="specialist-info">
            <span class="specialty-title">{{ $professional->rol === 'psiquiatra' ? 'Psiquiatra' : 'Psicólogo' }}</span>
            <h3>{{ $professional->nombre_completo }} <span class="verified-icon">✓</span></h3>
            <p>{{ $profile?->especialidad_principal ?? 'Especialidad registrada' }}</p>
          </div>
        </div>
        <p class="specialist-bio">{{ $profile?->biografia ?: 'Profesional aprobado por administración. Revisa su perfil completo para conocer formación, enfoque y disponibilidad.' }}</p>
        <div class="specialist-tags">
          <span class="tag">{{ $profile?->experiencia_anios ? $profile->experiencia_anios.' años' : 'Experiencia registrada' }}</span>
          <span class="tag">{{ $profile?->modalidad ?: 'Modalidad disponible' }}</span>
          @if($profile?->idiomas)<span class="tag">{{ $profile->idiomas }}</span>@endif
        </div>
        <div class="specialist-footer backend-specialist-footer">
          <div class="availability"><span class="status-dot green"></span> Disponible para primera cita</div>
          <strong class="specialist-price">${{ number_format($amount, 0) }} MXN</strong>
          <div class="action-buttons">
            <button type="button" class="btn-outline js-open-specialist" data-modal="specialist-modal-{{ $professional->id }}">Ver perfil</button>
            <a class="btn-primary" href="{{ route('paciente.agendar-cita', ['especialista' => \Illuminate\Support\Str::slug($professional->nombre_completo), 'id' => $professional->id]) }}">Agendar</a>
          </div>
        </div>
      </article>

      <div class="modal-overlay specialist-profile-modal profile-modal-v2" id="specialist-modal-{{ $professional->id }}" aria-hidden="true">
        <div class="specialist-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title-{{ $professional->id }}">
          <button class="modal-close-btn js-close-specialist" type="button" aria-label="Cerrar">×</button>
          <header class="specialist-modal-hero">
            <div class="profile-modal-avatar">{{ $professional->initials() }}</div>
            <div class="profile-modal-heading">
              <h2 id="modal-title-{{ $professional->id }}">{{ $professional->nombre_completo }} <span class="verified-icon">✓</span></h2>
              <p>{{ $profile?->especialidad_principal ?? ($professional->rol === 'psiquiatra' ? 'Psiquiatría' : 'Psicología clínica') }}</p>
              <span class="backend-chip ok">Perfil verificado por IRIS</span>
            </div>
          </header>
          <div class="specialist-modal-body-v2">
            <div class="specialist-modal-main">
              <section class="profile-detail-section">
                <h3>Acerca de mí</h3>
                <p>{{ $profile?->biografia ?: $profile?->presentacion ?: 'Este especialista cuenta con perfil aprobado por administración y suscripción activa.' }}</p>
              </section>

              <section class="profile-detail-section">
                <h3>Formación académica</h3>
                <ul class="profile-education-list">
                  @forelse($formations as $formation)
                    <li>{{ $formation }}</li>
                  @empty
                    @if($profile?->titulo_profesional)<li>{{ $profile->titulo_profesional }}</li>@endif
                    @if($profile?->institucion)<li>{{ $profile->institucion }}</li>@endif
                    @if($profile?->posgrado)<li>{{ $profile->posgrado }}</li>@endif
                    @if(! $profile?->titulo_profesional && ! $profile?->institucion && ! $profile?->posgrado)<li>Formación registrada para revisión administrativa.</li>@endif
                  @endforelse
                </ul>
              </section>

              <section class="profile-detail-section">
                <h3>Especialidades</h3>
                <div class="profile-specialty-tags">
                  @forelse($specialties as $specialty)
                    <span>{{ $specialty }}</span>
                  @empty
                    @foreach((array)($profile?->enfoques ?: ['Atención clínica']) as $specialty)
                      <span>{{ $specialty }}</span>
                    @endforeach
                  @endforelse
                </div>
              </section>

              <section class="profile-detail-section profile-extra-grid">
                <div><h4>Idiomas</h4><p>{{ $profile?->idiomas ?: 'Español' }}</p></div>
                <div><h4>Modalidad</h4><p>{{ $profile?->modalidad ?: 'A coordinar' }}</p></div>
                <div><h4>Ubicación</h4><p>{{ $profile?->ubicacion ?: 'Por confirmar' }}</p></div>
              </section>
            </div>

            <aside class="profile-booking-card">
              <h3>Información de consulta</h3>
              <div class="profile-price-row"><span>Tarifa por sesión:</span><strong>${{ number_format($amount,0) }} MXN</strong></div>
              <div class="profile-divider"></div>
              <h4>Días de atención</h4>
              <div class="profile-days-row">
                @foreach(['lunes','martes','miércoles','jueves','viernes','sábado','domingo'] as $day)
                  @php($activeDay = $careDays->contains($day) || $careDays->contains(str_replace('é','e',$day)))
                  <span class="profile-day {{ $activeDay ? 'active' : '' }}">{{ $dayMap[$day] ?? strtoupper(mb_substr($day,0,1)) }}</span>
                @endforeach
              </div>
              <div class="next-slot-box"><span>Próximo espacio:</span><strong>{{ $profile?->proximo_espacio ?: 'Por confirmar con el profesional' }}</strong></div>
              <a class="btn-primary w-100" href="{{ route('paciente.agendar-cita', ['especialista' => \Illuminate\Support\Str::slug($professional->nombre_completo), 'id' => $professional->id]) }}">Agendar cita ahora</a>
            </aside>
          </div>
        </div>
      </div>
    @empty
      <div class="backend-empty directory-empty-message">Todavía no hay especialistas aprobados y activos.</div>
    @endforelse
  </section>
  <div class="backend-empty directory-empty-message" id="specialist-no-results" hidden>No encontramos especialistas con esa búsqueda.</div>
</main>
@include('partials.floating-auxilio')
<script>
(() => {
  const normalize = value => (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  const input = document.getElementById('specialist-filter');
  const role = document.getElementById('specialist-role-filter');
  const cards = Array.from(document.querySelectorAll('.backend-specialist-card'));
  const empty = document.getElementById('specialist-no-results');

  function filterSpecialists() {
    const q = normalize(input?.value);
    const selectedRole = role?.value || '';
    let visible = 0;
    cards.forEach(card => {
      const text = normalize(card.dataset.search);
      const roleMatch = !selectedRole || card.dataset.role === selectedRole;
      const textMatch = !q || text.includes(q);
      const show = roleMatch && textMatch;
      card.hidden = !show;
      if (show) visible++;
    });
    if (empty) empty.hidden = visible !== 0 || cards.length === 0;
  }

  input?.addEventListener('input', filterSpecialists);
  role?.addEventListener('change', filterSpecialists);
  filterSpecialists();

  document.querySelectorAll('.js-open-specialist').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modal);
      if (!modal) return;
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
    });
  });
  document.querySelectorAll('.specialist-profile-modal').forEach(modal => {
    modal.addEventListener('click', event => {
      if (event.target === modal || event.target.classList.contains('js-close-specialist')) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
      }
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.specialist-profile-modal.active').forEach(modal => {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    });
    document.body.classList.remove('modal-open');
  });
})();
</script>
</body>
</html>
