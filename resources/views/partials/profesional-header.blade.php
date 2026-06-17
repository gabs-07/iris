<header class="dashboard-header">
  <div class="welcome-text">
    <h1>{{ $title ?? 'Panel clínico' }}</h1>
    <p>{{ $subtitle ?? 'Administra solicitudes, agenda y seguimiento clínico de tus pacientes.' }}</p>
  </div>
  <div class="header-actions">
    @include('partials.notification-modal')
    <div class="profile-chip">
      <span class="avatar">{{ auth()->user()->initials() }}</span>
      <div class="profile-info"><strong>{{ auth()->user()->nombre_completo }}</strong><span>{{ ucfirst(auth()->user()->rol) }}</span></div>
    </div>
  </div>
</header>
