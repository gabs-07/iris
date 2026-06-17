<header class="dashboard-header">
  <div class="welcome-text">
    <h1><?php echo e($title ?? 'Mi portal'); ?></h1>
    <p><?php echo e($subtitle ?? 'Gestiona tu proceso de atención y bienestar emocional desde un espacio seguro.'); ?></p>
  </div>
  <div class="header-actions">
    <?php echo $__env->make('partials.notification-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="profile-chip">
      <span class="avatar"><?php echo e(auth()->user()->initials()); ?></span>
      <div class="profile-info"><strong><?php echo e(auth()->user()->nombre_completo); ?></strong><span>Paciente</span></div>
    </div>
  </div>
</header>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/partials/paciente-header.blade.php ENDPATH**/ ?>