<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-role" content="{{ auth()->user()->rol }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas de sesión | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sesion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-psicologo.js') }}"></script>
<main class="dashboard-main">
    @include('partials.profesional-header', ['title'=>'Notas de sesión', 'subtitle'=>'Registra notas clínicas cifradas para citas aceptadas o completadas.'])
    @include('partials.frontend-alerts')

    <section class="dash-card">
        <div class="card-top">
            <div>
                <h2>Videollamadas disponibles</h2>
                <p class="card-subtitle">Usa el enlace de anfitrión para iniciar la reunión de Zoom asociada a cada cita.</p>
            </div>
        </div>
        <div class="appointment-list">
            @forelse($appointments as $appointment)
                @php($videoUrl = $appointment->professional_video_url)
                <div class="appointment-item">
                    <div class="appointment-time">{{ optional($appointment->starts_at)->format('H:i') }}</div>
                    <div class="appointment-details">
                        <strong>{{ $appointment->patient?->nombre_completo ?? 'Paciente no disponible' }}</strong>
                        <span>{{ optional($appointment->starts_at)->format('d/m/Y') }} · {{ $appointment->modality }}</span>
                        @if($appointment->zoom_meeting_id)
                            <small>Zoom ID: {{ $appointment->zoom_meeting_id }} @if($appointment->zoom_password) · Contraseña: {{ $appointment->zoom_password }} @endif</small>
                        @else
                            <small>Sin reunión Zoom registrada</small>
                        @endif
                    </div>
                    @if(($appointment->is_video_session_available ?? false) && $videoUrl)
                        <a class="btn-primary-small" href="{{ $videoUrl }}" target="_blank" rel="noopener">Iniciar videollamada</a>
                    @elseif($appointment->status === 'accepted' && $appointment->starts_at && $appointment->starts_at->gt(now()))
                        <span class="item-status">Disponible 2 min antes · {{ $appointment->starts_at->format('H:i') }}</span>
                    @else
                        <span class="item-status">Ventana cerrada</span>
                    @endif
                </div>
            @empty
                <div class="backend-empty">No hay citas disponibles para videollamada o notas.</div>
            @endforelse
        </div>
    </section>

    <form class="dash-card backend-form-grid" method="POST" action="{{ route('profesional.sesion.store') }}">
        @csrf
        @if($appointments->isEmpty())
            <div class="backend-empty">No hay citas disponibles para registrar notas.</div>
        @else
            <label class="backend-field">Cita
                <select name="appointment_id" required>
                    @foreach($appointments as $appointment)
                        <option value="{{ $appointment->id }}">{{ $appointment->patient?->nombre_completo }} — {{ optional($appointment->starts_at)->format('d/m/Y H:i') }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label class="backend-field">Tipo
            <select name="note_type">
                <option value="session">Nota de sesión</option>
                <option value="patient">Nota de paciente</option>
            </select>
        </label>
        <label class="backend-field">Notas<textarea name="notes" required></textarea></label>
        <button class="btn-primary" @if($appointments->isEmpty()) disabled @endif>Guardar nota</button>
    </form>
</main>
</body>
</html>
