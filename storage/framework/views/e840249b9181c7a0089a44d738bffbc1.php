<?php
  $notificationUser = auth()->user();
  $notificationItems = $notificationUser?->notifications()->latest()->take(8)->get() ?? collect();
  $unreadCount = $notificationUser?->unreadNotifications()->count() ?? 0;
?>
<button type="button" class="notification-chip notification-trigger" data-notification-open aria-label="Abrir notificaciones">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M10 17a2 2 0 0 0 4 0"/></svg>
  <?php if($unreadCount > 0): ?><span class="notification-dot"><?php echo e($unreadCount > 9 ? '9+' : $unreadCount); ?></span><?php endif; ?>
</button>

<div class="notification-modal-backdrop" data-notification-modal hidden>
  <section class="notification-modal" role="dialog" aria-modal="true" aria-labelledby="notification-modal-title">
    <div class="notification-modal-header">
      <div>
        <span class="backend-chip muted">Centro de actividad</span>
        <h2 id="notification-modal-title">Notificaciones</h2>
        <p>Se muestran aquí mismo, sin abrir otra pantalla.</p>
      </div>
      <button type="button" class="notification-modal-close" data-notification-close aria-label="Cerrar">×</button>
    </div>

    <div class="notification-modal-list">
      <?php $__empty_1 = true; $__currentLoopData = $notificationItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <article class="notification-item <?php echo e($notification->read_at ? 'is-read' : 'is-unread'); ?>">
          <div class="notification-item-content">
            <strong><?php echo e($notification->data['title'] ?? 'Notificación'); ?></strong>
            <p><?php echo e($notification->data['message'] ?? 'Tienes una actualización en IRIS.'); ?></p>
            <small><?php echo e($notification->created_at->diffForHumans()); ?></small>
          </div>
          <div class="notification-item-actions">
            <?php if(! $notification->read_at): ?>
              <form method="POST" action="<?php echo e(route('notifications.mark', $notification->id)); ?>">
                <?php echo csrf_field(); ?>
                <button class="btn-outline btn-sm" type="submit">Marcar leída</button>
              </form>
            <?php endif; ?>
            <?php if(!empty($notification->data['url'])): ?>
              <a class="btn-primary btn-sm" href="<?php echo e($notification->data['url']); ?>">Ver relacionado</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="backend-empty">No tienes notificaciones por ahora.</div>
      <?php endif; ?>
    </div>

    <?php if($notificationItems->isNotEmpty()): ?>
      <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>" class="notification-modal-footer">
        <?php echo csrf_field(); ?>
        <button class="btn-outline" type="submit">Marcar todas como leídas</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<script>
(function(){
  const openBtn = document.querySelector('[data-notification-open]');
  const modal = document.querySelector('[data-notification-modal]');
  const closeBtn = document.querySelector('[data-notification-close]');
  if(!openBtn || !modal) return;
  const open = () => { modal.hidden = false; document.body.classList.add('modal-open'); };
  const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); };
  openBtn.addEventListener('click', open);
  closeBtn?.addEventListener('click', close);
  modal.addEventListener('click', (event) => { if(event.target === modal) close(); });
  document.addEventListener('keydown', (event) => { if(event.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/partials/notification-modal.blade.php ENDPATH**/ ?>