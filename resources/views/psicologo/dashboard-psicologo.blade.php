<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-role" content="{{ auth()->user()->rol }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel clínico | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/fontawesome-local.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/agenda.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
@php($modoEscuchaActivo = (bool) ($user->professionalProfile?->modo_escucha_activo ?? false))
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-psicologo.js') }}"></script>

<main class="dashboard-main">
    <header class="dashboard-header">
        <div class="header-copy">
            <div class="welcome-text">
                <h1>Resumen Clínico</h1>
                <p>Resumen clínico, agenda y próximas tareas para tu jornada.</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="escucha-toggle-container" title="Disponibilidad para recibir solicitudes de auxilio">
                <div class="escucha-copy">
                    <span class="toggle-label">Modo Escucha</span>
                    <small id="modo-escucha-status">{{ $modoEscuchaActivo ? 'Disponible para auxilio' : 'No disponible' }}</small>
                </div>
                <label class="switch" for="modo-escucha">
                    <input type="checkbox" id="modo-escucha" data-url="{{ route('profesional.modo-escucha.update') }}" {{ $modoEscuchaActivo ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>
            @include('partials.notification-modal')
            <div class="profile-chip">
                <span class="avatar">{{ auth()->user()->initials() }}</span>
                <div class="profile-info">
                    <strong>{{ auth()->user()->nombre_completo }}</strong>
                    <span>{{ auth()->user()->professionalProfile?->especialidad_principal ?: ucfirst(auth()->user()->rol) }}</span>
                </div>
            </div>
        </div>
    </header>

    @include('partials.frontend-alerts')

    @if(($activeAuxilioAppointments ?? collect())->isNotEmpty())
        <section class="auxilio-professional-panel">
            <div>
                <span class="backend-chip">Auxilio activo</span>
                <h2>Solicitud de apoyo inmediato</h2>
                <p>Un paciente fue conectado contigo porque tienes activado el Modo Escucha.</p>
            </div>
            <div class="auxilio-professional-list">
                @foreach($activeAuxilioAppointments as $auxilioAppointment)
                    <article class="auxilio-professional-item">
                        <div>
                            <strong>{{ $auxilioAppointment->patient?->nombre_completo ?? 'Paciente IRIS' }}</strong>
                            <span>{{ optional($auxilioAppointment->starts_at)->format('H:i') }} · {{ $auxilioAppointment->folio }}</span>
                        </div>
                        @if($auxilioAppointment->professional_video_url)
                            <a class="btn-primary-small" href="{{ $auxilioAppointment->professional_video_url }}" target="_blank" rel="noopener">Entrar a Zoom</a>
                        @else
                            <span class="item-status">Sin enlace Zoom</span>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="metrics-grid">
        <article class="metric-card">
            <div class="metric-header"><span>Pacientes activos</span><strong>{{ $activePatientsCount }}</strong></div>
            <p>Pacientes con sesiones aceptadas o completadas.</p>
        </article>
        <article class="metric-card">
            <div class="metric-header"><span>Citas hoy</span><strong>{{ $todayAppointments->count() }}</strong></div>
            <p>Sesiones confirmadas y listas para iniciar.</p>
        </article>
        <article class="metric-card">
            <div class="metric-header"><span>Tareas pendientes</span><strong>{{ $pendingTasksCount }}</strong></div>
            <p>Seguimientos y ejercicios sin revisar.</p>
        </article>
    </section>

    <section class="cards-grid">
        <article class="dash-card appointments-card">
            <div class="card-top">
                <div>
                    <h2>Citas de hoy</h2>
                    <p class="card-subtitle">Organiza tu agenda y accede a cada sesión con un solo clic.</p>
                </div>
                <a href="{{ route('profesional.agenda') }}" class="btn-outline btn-sm">Ver calendario</a>
            </div>
            <div class="appointment-list">
                @forelse($todayAppointments as $appointment)
                    <div class="appointment-item">
                        <div class="appointment-time">{{ optional($appointment->starts_at)->format('H:i') }}</div>
                        <div class="appointment-details">
                            <strong>{{ $appointment->patient?->nombre_completo ?? 'Paciente no disponible' }}</strong>
                            <span>{{ $appointment->reason }} · {{ $appointment->modality }}</span>
                            <small>Folio: {{ $appointment->id }}</small>
                        </div>
                        @if(($appointment->is_video_session_available ?? false) && $appointment->professional_video_url)
                            <a class="btn-primary-small" href="{{ $appointment->professional_video_url }}" target="_blank" rel="noopener">Entrar</a>
                        @elseif($appointment->starts_at && $appointment->starts_at->gt(now()))
                            <span class="item-status">Disponible 2 min antes · {{ $appointment->starts_at->format('H:i') }}</span>
                        @else
                            <span class="item-status">Ventana cerrada</span>
                        @endif
                    </div>
                @empty
                    <div class="backend-empty">No hay citas confirmadas para hoy.</div>
                @endforelse
            </div>
        </article>

        <article class="dash-card requests-card">
            <div class="card-top">
                <div>
                    <h2>Nuevas solicitudes de cita</h2>
                    <p class="card-subtitle">Revisa cada solicitud antes de aceptar, proponer otro horario o rechazar.</p>
                </div>
                <a href="{{ route('profesional.agenda') }}#solicitudes" class="btn-outline btn-sm">Abrir en agenda</a>
            </div>
            <div class="request-list">
                @forelse($pendingRequests->take(3) as $appointment)
                    <div class="request-item">
                        <div class="request-info">
                            <strong>{{ $appointment->patient?->nombre_completo ?? 'Paciente no disponible' }}</strong>
                            <span>📅 {{ optional($appointment->starts_at)->translatedFormat('j M, H:i') }} h · {{ $appointment->modality }}</span>
                            <span class="request-note">“{{ \Illuminate\Support\Str::limit($appointment->notes ?: $appointment->reason, 90) }}”</span>
                        </div>
                        <div class="request-actions">
                            <a class="btn-primary-small" href="{{ route('profesional.agenda') }}#solicitudes">Ver detalles</a>
                        </div>
                    </div>
                @empty
                    <div class="backend-empty">Sin solicitudes nuevas.</div>
                @endforelse
            </div>
            <div class="requests-footer">
                <a href="{{ route('profesional.agenda') }}#solicitudes" class="btn-outline btn-sm full-width">Gestionar solicitudes en agenda →</a>
            </div>
        </article>
    </section>
</main>
<script src="{{ asset('js/dashboard-psicologo.js') }}"></script>
</body>
</html>
