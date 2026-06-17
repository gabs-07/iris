<header class="dashboard-header">
  <div class="welcome-text">
    <h1>{{ $title ?? 'Mi portal' }}</h1>
    <p>{{ $subtitle ?? 'Gestiona tu proceso de atención y bienestar emocional desde un espacio seguro.' }}</p>
  </div>
  <div class="header-actions">
    @include('partials.notification-modal')
    <div class="profile-chip">
      <span class="avatar">{{ auth()->user()->initials() }}</span>
      <div class="profile-info"><strong>{{ auth()->user()->nombre_completo }}</strong><span>Paciente</span></div>
    </div>
  </div>
</header>
