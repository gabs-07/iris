<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis citas | IRIS</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/auxilio.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/gestion-citas.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/citas.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body appointment-page appointments-tabs-page">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-paciente.js')); ?>"></script>
<main class="dashboard-main">
  <?php echo $__env->make('partials.paciente-header', ['title'=>'Mis citas y sesiones', 'subtitle'=>'Consulta tus próximas sesiones, solicitudes y el historial de atención.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php
  $statusLabels = [
    'pending_payment' => 'Pendiente de pago',
    'pending' => 'Solicitada',
    'accepted' => 'Confirmada',
    'rescheduled' => 'Reagenda pendiente',
    'rejected' => 'Rechazada',
    'cancelled' => 'Cancelada',
    'completed' => 'Completada',
    'missed' => 'Perdida / sin entrar',
  ];
  $paymentLabels = ['pending'=>'Pago pendiente','paid'=>'Pagada','refunded'=>'Reembolsada','waived'=>'Sin cobro'];
  $now = now(config('app.timezone'));
  $upcoming = $appointments->filter(fn($a) => $a->status === 'accepted' && ($a->is_pending_or_active_session ?? false))->sortBy('starts_at')->values();
  $requests = $appointments->filter(fn($a) => in_array($a->status, ['pending_payment','pending','rescheduled'], true))->sortBy('starts_at')->values();
  $history = $appointments->filter(fn($a) => in_array($a->effective_status, ['completed', 'missed'], true))->values();
  $cancelled = $appointments->filter(fn($a) => in_array($a->status, ['cancelled','rejected'], true))->values();
  $nextAppointment = $upcoming->sortBy('starts_at')->first();
  $monthNames = ['01'=>'ENE','02'=>'FEB','03'=>'MAR','04'=>'ABR','05'=>'MAY','06'=>'JUN','07'=>'JUL','08'=>'AGO','09'=>'SEP','10'=>'OCT','11'=>'NOV','12'=>'DIC'];
  $tabs = ['proximas' => ['label' => 'Próximas', 'items' => $upcoming], 'solicitudes' => ['label' => 'Solicitudes', 'items' => $requests], 'historial' => ['label' => 'Historial', 'items' => $history], 'canceladas' => ['label' => 'Canceladas', 'items' => $cancelled]];
?>
<section class="appointment-summary patient-citas-summary">
  <article class="metric-card"><span>Próxima cita</span><strong><?php echo e($nextAppointment?->starts_at?->format('d') ?? '—'); ?></strong><small><?php echo e($nextAppointment?->starts_at?->format('d/m/Y H:i') ?? 'Sin cita confirmada'); ?></small></article>
  <article class="metric-card"><span>Citas activas</span><strong><?php echo e($upcoming->count()); ?></strong><small>Sesiones confirmadas</small></article>
  <article class="metric-card"><span>Solicitudes</span><strong><?php echo e($requests->count()); ?></strong><small>Pago, revisión o reagenda</small></article>
</section>

<section class="patient-tabs-toolbar appointment-tabs-native">
  <div class="tabs-container patient-appointment-tabs" role="tablist" aria-label="Secciones de citas">
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <button class="tab-btn <?php echo e($loop->first ? 'active' : ''); ?>" type="button" data-tab="<?php echo e($key); ?>" aria-selected="<?php echo e($loop->first ? 'true' : 'false'); ?>"><?php echo e($tab['label']); ?></button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <a class="new-appointment-floating" href="<?php echo e(route('paciente.buscar-especialista')); ?>" title="Nueva cita" aria-label="Nueva cita">+</a>
</section>

<?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <section class="tab-content appointment-tab-content <?php echo e($loop->first ? 'active' : ''); ?>" id="tab-<?php echo e($key); ?>" data-tab-panel="<?php echo e($key); ?>">
    <div class="appointments-list appointment-card-row patient-appointments-native <?php echo e($key); ?>-tab">
      <?php $__empty_1 = true; $__currentLoopData = $tab['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $profile = $appointment->professional?->professionalProfile;
          $startsAt = $appointment->starts_at;
          $month = $startsAt ? ($monthNames[$startsAt->format('m')] ?? strtoupper($startsAt->format('M'))) : '---';
          $day = $startsAt?->format('d') ?? '--';
          $year = $startsAt?->format('Y') ?? '';
          $timeLabel = $startsAt?->format('H:i') ?? ($appointment->appointment_time ?: 'Por definir');
          $effectiveStatus = $appointment->effective_status;
          $statusClass = match($effectiveStatus) { 'accepted' => 'confirmed', 'completed' => 'completed', 'missed', 'cancelled', 'rejected' => 'cancelled', default => 'pending' };
          $displayFolio = 'ID '.$appointment->id;
          $professionalName = $appointment->professional?->nombre_completo ?? 'Especialista pendiente';
          $specialty = $profile?->especialidad_principal ?? (($appointment->professional?->rol === 'psiquiatra') ? 'Psiquiatría' : 'Psicología');
          $modality = ucfirst($appointment->modality ?? 'Modalidad');
          $reason = $appointment->reason ?: 'Motivo pendiente';
          $videoLink = $appointment->patient_video_url;
          $isSessionMoment = $appointment->is_video_session_available ?? false;
          $sessionAccessEnds = $appointment->session_access_ends_at;
          $hasPrimaryAction = false;
          $requestTitle = match($appointment->status) {
              'pending_payment' => 'Pago pendiente de cita',
              'pending' => 'Nueva cita solicitada',
              'rescheduled' => 'Solicitud de reagenda',
              default => $professionalName,
          };
          $requestDescription = match($appointment->status) {
              'pending_payment' => 'Completa el pago para enviar la solicitud al especialista.',
              'pending' => 'Pendiente de confirmación por el especialista.',
              'rescheduled' => 'El especialista propuso un nuevo horario. Acepta la propuesta o cancela la cita.',
              default => $modality.' · '.$reason,
          };
        ?>
        <article class="appt-card-compact patient-appt-card patient-appt-native <?php echo e($statusClass); ?> <?php echo e($key === 'solicitudes' ? 'request-row' : ''); ?>" data-appointment-id="<?php echo e($appointment->id); ?>">
          <div class="appt-date-small">
            <span class="month"><?php echo e($month); ?></span>
            <strong class="day"><?php echo e($day); ?></strong>
            <span class="year"><?php echo e($year); ?></span>
          </div>

          <div class="appt-details">
            <div class="appt-header-inline">
              <span class="status-badge <?php echo e($statusClass); ?>"><?php echo e($statusLabels[$effectiveStatus] ?? ucfirst($effectiveStatus)); ?></span>
              <span class="time"><?php echo e($timeLabel); ?> hrs</span>
              <span class="folio"><?php echo e($displayFolio); ?></span>
            </div>

            <div class="doc-info-compact patient-appointment-copy">
              <div>
                <h3><?php echo e($key === 'solicitudes' ? $requestTitle : $professionalName); ?></h3>
                <?php if($key === 'solicitudes'): ?>
                  <span><?php echo e($professionalName); ?> · <?php echo e($reason); ?> · <?php echo e($modality); ?></span>
                  <p class="request-meta"><?php echo e($requestDescription); ?></p>
                <?php else: ?>
                  <span><?php echo e($modality); ?> · <?php echo e($reason); ?></span>
                  <?php if($specialty): ?><small><?php echo e($specialty); ?></small><?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <?php if($appointment->status === 'rescheduled'): ?>
              <div class="reschedule-note"><strong>Propuesta:</strong> <?php echo e(optional($appointment->reschedule_date)->format('d/m/Y')); ?> <?php echo e($appointment->reschedule_time); ?> <?php if($appointment->reschedule_proposal): ?> · <?php echo e($appointment->reschedule_proposal); ?> <?php endif; ?></div>
            <?php endif; ?>

            <div class="appointment-mini-meta">
              <span><strong>Pago:</strong> <?php echo e($paymentLabels[$appointment->payment_status] ?? ucfirst($appointment->payment_status)); ?></span>
              <span><strong>Total:</strong> $<?php echo e(number_format((float) $appointment->amount, 2)); ?> MXN</span>
            </div>
          </div>

          <div class="appt-actions">
            <?php if($key === 'proximas'): ?>
              <?php if($isSessionMoment && $videoLink): ?>
                <?php $hasPrimaryAction = true; ?>
                <a class="btn-primary btn-sm" href="<?php echo e(route('paciente.sesion', ['appointment'=>$appointment->id])); ?>">Entrar a sesión</a>
              <?php else: ?>
                <span class="session-muted"><?php echo e($startsAt && $startsAt->lte($now) ? 'Disponible hasta '.optional($sessionAccessEnds)->format('H:i').' hrs' : 'Disponible 2 min antes'); ?></span>
              <?php endif; ?>
              <div class="action-links">
                <button class="btn-link" type="button" data-open-reschedule data-appointment="<?php echo e($appointment->id); ?>" data-specialist="<?php echo e($professionalName); ?>" data-current="<?php echo e($startsAt?->format('d/m/Y H:i') ?? 'Por definir'); ?>">Reagendar</button>
                <form class="inline-form" method="POST" action="<?php echo e(route('paciente.gestion-citas.cancelar',$appointment)); ?>"><?php echo csrf_field(); ?><input type="hidden" name="cancel_reason" value="Cancelada desde panel del paciente"><button class="btn-link danger" type="submit" onclick="return confirm('¿Cancelar esta cita?')">Cancelar cita</button></form>
              </div>
            <?php elseif($key === 'solicitudes'): ?>
              <div class="request-actions-bottom">
                <?php if($appointment->status === 'pending_payment'): ?>
                  <a class="btn-primary btn-sm" href="<?php echo e(route('paciente.pago-cita', ['appointment'=>$appointment->id])); ?>">Pagar</a>
                <?php endif; ?>
                <?php if($appointment->status === 'rescheduled'): ?>
                  <form class="inline-form" method="POST" action="<?php echo e(route('paciente.gestion-citas.aceptar-reagenda',$appointment)); ?>"><?php echo csrf_field(); ?><button class="btn-primary btn-sm" type="submit">Aceptar horario</button></form>
                <?php endif; ?>
                <?php if($appointment->status === 'pending' && $appointment->requested_by === 'profesional'): ?>
                  <form class="inline-form" method="POST" action="<?php echo e(route('paciente.gestion-citas.aceptar-solicitud',$appointment)); ?>"><?php echo csrf_field(); ?><button class="btn-primary btn-sm" type="submit">Aceptar cita</button></form>
                <?php endif; ?>
                <?php if($appointment->status !== 'pending_payment'): ?>
                  <button class="btn-outline btn-sm" type="button" data-open-reschedule data-appointment="<?php echo e($appointment->id); ?>" data-specialist="<?php echo e($professionalName); ?>" data-current="<?php echo e($startsAt?->format('d/m/Y H:i') ?? 'Por definir'); ?>">Reagendar</button>
                <?php endif; ?>
                <?php if(!in_array($effectiveStatus, ['completed','cancelled','rejected','missed'], true)): ?>
                  <form class="inline-form" method="POST" action="<?php echo e(route('paciente.gestion-citas.cancelar',$appointment)); ?>"><?php echo csrf_field(); ?><input type="hidden" name="cancel_reason" value="Cancelada desde panel del paciente"><button class="btn-link danger" type="submit" onclick="return confirm('¿Cancelar esta cita?')">Cancelar cita</button></form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="backend-empty">No hay registros en esta sección.</div>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</main>
<div class="iris-action-modal-backdrop" id="patient-reschedule-modal" hidden>
  <section class="iris-action-modal" role="dialog" aria-modal="true" aria-labelledby="patient-reschedule-title">
    <button class="iris-modal-close" type="button" data-close-reschedule aria-label="Cerrar">×</button>
    <p class="section-kicker">Solicitud de reagenda</p>
    <h2 id="patient-reschedule-title">Proponer nuevo horario</h2>
    <p class="muted-copy" id="patient-reschedule-copy">Tu especialista revisará la propuesta.</p>
    <form method="POST" id="patient-reschedule-form" class="modal-form-grid">
      <?php echo csrf_field(); ?>
      <div class="form-row two">
        <label>Nueva fecha<input type="date" name="reschedule_date" required min="<?php echo e(today()->toDateString()); ?>"></label>
        <label>Nueva hora<input type="time" name="reschedule_time" required></label>
      </div>
      <label>Mensaje para el especialista<textarea name="reschedule_proposal" rows="4" placeholder="Explica brevemente por qué necesitas reagendar."></textarea></label>
      <div class="modal-actions"><button class="btn-outline" type="button" data-close-reschedule>Cancelar</button><button class="btn-primary" type="submit">Enviar solicitud</button></div>
    </form>
  </section>
</div>
<?php echo $__env->make('partials.floating-auxilio', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
(() => {
  const buttons = Array.from(document.querySelectorAll('[data-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));
  function activate(tab) {
    buttons.forEach(btn => { const active = btn.dataset.tab === tab; btn.classList.toggle('active', active); btn.setAttribute('aria-selected', active ? 'true' : 'false'); });
    panels.forEach(panel => panel.classList.toggle('active', panel.dataset.tabPanel === tab));
  }
  buttons.forEach(btn => btn.addEventListener('click', () => activate(btn.dataset.tab)));
  const rescheduleModal = document.getElementById('patient-reschedule-modal');
  const rescheduleForm = document.getElementById('patient-reschedule-form');
  const rescheduleCopy = document.getElementById('patient-reschedule-copy');
  document.querySelectorAll('[data-open-reschedule]').forEach(btn => btn.addEventListener('click', () => {
    const appointmentId = btn.dataset.appointment;
    if (rescheduleForm && appointmentId) rescheduleForm.action = `/paciente/gestion-citas/${appointmentId}/reagendar`;
    if (rescheduleCopy) rescheduleCopy.textContent = `Cita con ${btn.dataset.specialist || 'tu especialista'} · horario actual: ${btn.dataset.current || 'por definir'}.`;
    rescheduleModal?.removeAttribute('hidden');
  }));
  function closeRescheduleModal(){ rescheduleModal?.setAttribute('hidden', 'hidden'); }
  document.querySelectorAll('[data-close-reschedule]').forEach(btn => btn.addEventListener('click', closeRescheduleModal));
  rescheduleModal?.addEventListener('click', event => { if (event.target === rescheduleModal) closeRescheduleModal(); });
})();
</script>
</body>
</html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/paciente/gestion-citas.blade.php ENDPATH**/ ?>