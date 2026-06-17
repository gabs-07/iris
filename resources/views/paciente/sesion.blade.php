<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de sesión | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sesion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="session-page" data-session-role="paciente">
<main class="session-room">
    @php($videoUrl = $appointment?->patient_video_url)
    <section class="session-locked dash-card">
        @if($appointment && $videoUrl)
            <span class="branding-badge">Sesión confirmada</span>
            <h1>{{ $appointment->professional?->nombre_completo }}</h1>
            <p>{{ optional($appointment->starts_at)->format('d/m/Y H:i') }} · {{ $appointment->modality }}</p>
            <p class="muted-copy">La sala está disponible desde 2 minutos antes y hasta 1 hora después de la hora programada.</p>
            @if($appointment->zoom_meeting_id)
                <p class="muted-copy">Reunión Zoom: {{ $appointment->zoom_meeting_id }} @if($appointment->zoom_password) · Contraseña: {{ $appointment->zoom_password }} @endif</p>
            @endif
            <a class="btn-primary" href="{{ $videoUrl }}" target="_blank" rel="noopener">Abrir videollamada</a>
            <a class="btn-outline" href="{{ route('paciente.gestion-citas') }}">Volver a mis citas</a>
        @else
            <span class="branding-badge">Sin sala activa</span>
            <h1>No hay una sala disponible</h1>
            <p>Solo podrás entrar cuando la cita esté aceptada, exista enlace de videollamada y estés dentro de la ventana de acceso: desde 2 minutos antes y hasta 1 hora después de la hora programada.</p>
            <a class="btn-primary" href="{{ route('paciente.gestion-citas') }}">Ver mis citas</a>
        @endif
    </section>
</main>
</body>
</html>
