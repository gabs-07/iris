<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auxilio emocional | IRIS</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/auxilio.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-paciente.js')); ?>"></script>
<main class="dashboard-main">
    <?php echo $__env->make('partials.paciente-header', ['title'=>'Auxilio emocional', 'subtitle'=>'Conexión inmediata con un profesional en Modo Escucha. IRIS no sustituye servicios de emergencia.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="dashboard-section-grid auxilio-grid-enhanced">
        <article class="dash-card auxilio-card-highlight">
            <span class="backend-chip">Modo Escucha</span>
            <h2>Conectar con un profesional disponible</h2>
            <p>Al solicitar auxilio, IRIS busca automáticamente a un profesional aprobado con Modo Escucha activo, incluyendo doctor interno cuando corresponde. Si hay disponibilidad, se crea una reunión de Zoom y aparece el botón para entrar.</p>

            <?php if($activeAuxilio): ?>
                <div class="auxilio-connected-box">
                    <strong>Profesional conectado</strong>
                    <span><?php echo e($activeAuxilio->professional?->nombre_completo ?? 'Especialista IRIS'); ?></span>
                    <small>Folio: <?php echo e($activeAuxilio->folio); ?> · Disponible hasta <?php echo e(optional($activeAuxilio->session_access_ends_at)->format('H:i')); ?></small>
                </div>
                <?php if($activeAuxilio->patient_video_url): ?>
                    <a class="btn-primary btn-lg" href="<?php echo e($activeAuxilio->patient_video_url); ?>" target="_blank" rel="noopener">Entrar a videollamada ahora</a>
                    <?php if(session('auto_open_call')): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const url = <?php echo e(json_encode($activeAuxilio->patient_video_url)); ?>;
                                if (url && !window.__irisAuxilioOpened) {
                                    window.__irisAuxilioOpened = true;
                                    const popup = window.open(url, '_blank', 'noopener,noreferrer');
                                    if (!popup) {
                                        window.location.href = url;
                                    }
                                }
                            });
                        </script>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="backend-alert warning">La solicitud fue creada, pero todavía no hay enlace Zoom disponible. Revisa la configuración de Zoom.</div>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('paciente.auxilio.zoom')); ?>" class="auxilio-request-form">
                    <?php echo csrf_field(); ?>
                    <button class="btn-primary btn-lg" type="submit">Buscar profesional en Modo Escucha</button>
                </form>
            <?php endif; ?>
        </article>

        <article class="dash-card">
            <h2>Contacto de emergencia registrado</h2>
            <?php ($contact = auth()->user()->emergencyContact); ?>
            <div class="payment-summary-grid">
                <div><span>Nombre</span><strong><?php echo e($contact?->nombre ?: 'No registrado'); ?></strong></div>
                <div><span>Relación</span><strong><?php echo e($contact?->relacion ?: 'No registrada'); ?></strong></div>
                <div><span>Teléfono</span><strong><?php echo e($contact?->telefono ?: 'No registrado'); ?></strong></div>
            </div>
            <?php if($contact?->telefono): ?>
                <a class="btn-outline" href="tel:<?php echo e($contact->telefono); ?>">Llamar contacto</a>
            <?php endif; ?>
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
<?php /**PATH C:\laragon\www\iris-escom\resources\views/paciente/auxilio-paciente.blade.php ENDPATH**/ ?>