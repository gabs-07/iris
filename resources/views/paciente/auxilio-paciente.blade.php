<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auxilio emocional | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>
<main class="dashboard-main">
    @include('partials.paciente-header', ['title'=>'Auxilio emocional', 'subtitle'=>'Conexión inmediata con un profesional en Modo Escucha. IRIS no sustituye servicios de emergencia.'])
    @include('partials.frontend-alerts')

    <section class="dashboard-section-grid auxilio-grid-enhanced">
        <article class="dash-card auxilio-card-highlight">
            <span class="backend-chip">Modo Escucha</span>
            <h2>Conectar con un profesional disponible</h2>
            <p>Al solicitar auxilio, IRIS busca automáticamente a un profesional aprobado con Modo Escucha activo, incluyendo doctor interno cuando corresponde. Si hay disponibilidad, se crea una reunión de Zoom y aparece el botón para entrar.</p>

            @if($activeAuxilio)
                <div class="auxilio-connected-box">
                    <strong>Profesional conectado</strong>
                    <span>{{ $activeAuxilio->professional?->nombre_completo ?? 'Especialista IRIS' }}</span>
                    <small>Folio: {{ $activeAuxilio->folio }} · Disponible hasta {{ optional($activeAuxilio->session_access_ends_at)->format('H:i') }}</small>
                </div>
                @if($activeAuxilio->patient_video_url)
                    <a class="btn-primary btn-lg" href="{{ $activeAuxilio->patient_video_url }}" target="_blank" rel="noopener">Entrar a videollamada ahora</a>
                    @if(session('auto_open_call'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const url = {{ json_encode($activeAuxilio->patient_video_url) }};
                                if (url && !window.__irisAuxilioOpened) {
                                    window.__irisAuxilioOpened = true;
                                    const popup = window.open(url, '_blank', 'noopener,noreferrer');
                                    if (!popup) {
                                        window.location.href = url;
                                    }
                                }
                            });
                        </script>
                    @endif
                @else
                    <div class="backend-alert warning">La solicitud fue creada, pero todavía no hay enlace Zoom disponible. Revisa la configuración de Zoom.</div>
                @endif
            @else
                <form method="POST" action="{{ route('paciente.auxilio.zoom') }}" class="auxilio-request-form">
                    @csrf
                    <button class="btn-primary btn-lg" type="submit">Buscar profesional en Modo Escucha</button>
                </form>
            @endif
        </article>

        <article class="dash-card">
            <h2>Contacto de emergencia registrado</h2>
            @php($contact = auth()->user()->emergencyContact)
            <div class="payment-summary-grid">
                <div><span>Nombre</span><strong>{{ $contact?->nombre ?: 'No registrado' }}</strong></div>
                <div><span>Relación</span><strong>{{ $contact?->relacion ?: 'No registrada' }}</strong></div>
                <div><span>Teléfono</span><strong>{{ $contact?->telefono ?: 'No registrado' }}</strong></div>
            </div>
            @if($contact?->telefono)
                <a class="btn-outline" href="tel:{{ $contact->telefono }}">Llamar contacto</a>
            @endif
        </article>

        <article class="dash-card">
            <h2>Emergencia inmediata</h2>
            <p>Si existe riesgo de daño, llama a los servicios de emergencia locales o acude a una unidad médica cercana.</p>
            <a class="btn-outline" href="tel:911">Llamar 911</a>
        </article>
    </section>
</main>
</body>
</html>
