<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Diario | IRIS</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/auxilio.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/diario.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-paciente.js')); ?>"></script>
<main class="dashboard-main">
  <?php echo $__env->make('partials.paciente-header', ['title' => 'Mi Diario Personal', 'subtitle' => 'Registra tus emociones y conserva un historial privado de tu proceso.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <section class="dashboard-section-grid diary-page-grid">
    <form method="POST" action="<?php echo e(route('paciente.diario.store')); ?>" class="dash-card diary-card">
      <?php echo csrf_field(); ?>
      <div class="card-top">
        <div>
          <h2>Nueva entrada</h2>
          <p class="card-subtitle">Escribe una nota a cualquier hora. IRIS la agregará al registro del día con la hora exacta.</p>
        </div>
      </div>
      <div class="today-fixed-card">
        <span>Fecha de registro</span>
        <strong><?php echo e(now()->format('d/m/Y')); ?></strong>
        <small>La fecha queda fija al día actual y no puede modificarse. Todas las notas antes de las 12:00 AM se agrupan en este mismo registro.</small>
      </div>
      <label class="backend-field">Autorizar visualización del diario
        <select name="authorized_professional_id">
          <option value="">Solo yo</option>
          <?php $__currentLoopData = ($diaryProfessionals ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $professional): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($professional->id); ?>"><?php echo e($professional->nombre_completo); ?> · <?php echo e(ucfirst($professional->rol)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </label>
      <label class="backend-field">Título<input name="title" maxlength="180" placeholder="Ej. Cómo me sentí hoy"></label>
      <label class="diary-label" for="diary-content">Contenido</label>
      <textarea id="diary-content" class="diary-textarea" name="content" required placeholder="Describe tus pensamientos, emociones o situaciones relevantes."></textarea>
      <label class="backend-field">Estado de ánimo<input name="mood" maxlength="80" placeholder="Ej. Tranquilo, ansioso, motivado"></label>
      <div class="emoji-selector">
        <span class="emoji-selector-title">Emoji del día</span>
        <div class="emoji-options">
          <?php $__currentLoopData = ['🙂','😌','😟','😢','😡','💪']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emoji): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label class="emoji-option"><input type="radio" name="emoji" value="<?php echo e($emoji); ?>"><span><?php echo e($emoji); ?></span></label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
      <div class="diary-actions">
        <button class="btn-primary" type="submit">Guardar entrada</button>
        <span class="diary-helper">Solo tú podrás verlo, excepto si seleccionas un profesional autorizado de tu proceso clínico.</span>
      </div>
    </form>

    <section class="dash-card diary-history-card">
      <div class="card-top diary-card-header-actions">
        <div>
          <h2>Últimas entradas</h2>
          <p class="card-subtitle">Se muestran máximo 5 registros recientes.</p>
        </div>
        <a class="btn-outline btn-sm" href="<?php echo e(route('paciente.diario.todas')); ?>">Ver todas las entradas</a>
      </div>
      <div class="diary-summary"><span>Entradas guardadas</span><strong><?php echo e($diaryEntries->count()); ?></strong></div>
      <div class="diary-search-container">
        <svg class="diary-search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="search" class="diary-search-input" id="diary-filter" placeholder="Buscar en todas tus entradas..." autocomplete="off">
      </div>
      <div class="diary-entries" id="diary-entries">
        <?php $__empty_1 = true; $__currentLoopData = $diaryEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <article class="diary-entry" data-index="<?php echo e($loop->index); ?>" data-search="<?php echo e(e(($entry['title'] ?? '').' '.($entry['mood'] ?? '').' '.($entry['content'] ?? '').' '.($entry['date'] ?? ''))); ?>">
            <div class="diary-entry-top">
              <div><span class="diary-entry-date"><?php echo e($entry['date']); ?></span><h3><?php echo e($entry['title'] ?: 'Entrada del diario'); ?></h3></div>
              <span class="diary-entry-emoji"><?php echo e($entry['emoji'] ?: '📝'); ?></span>
            </div>
            <?php if(!empty($entry['notes'])): ?>
              <div class="diary-entry-text" style="display:grid;gap:10px;">
                <?php $__currentLoopData = $entry['notes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <div style="border-left:3px solid #2c7a5e;padding-left:10px;">
                    <strong><?php echo e($note['time'] ?? '--:--'); ?></strong>
                    <?php if(!empty($note['emoji'])): ?> <span><?php echo e($note['emoji']); ?></span> <?php endif; ?>
                    <?php if(!empty($note['mood'])): ?> <span class="backend-chip"><?php echo e($note['mood']); ?></span> <?php endif; ?>
                    <?php if(!empty($note['title'])): ?><p><strong><?php echo e($note['title']); ?></strong></p><?php endif; ?>
                    <p><?php echo e($note['content'] ?? ''); ?></p>
                  </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            <?php else: ?>
              <p class="diary-entry-text"><?php echo e($entry['content']); ?></p>
            <?php endif; ?>
            <?php if($entry['mood']): ?><span class="backend-chip"><?php echo e($entry['mood']); ?></span><?php endif; ?>
            <?php if(!empty($entry['authorized_professional'])): ?><span class="backend-chip">Compartido con <?php echo e($entry['authorized_professional']); ?></span><?php endif; ?>
          </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="diary-empty">No has escrito entradas todavía.</div>
        <?php endif; ?>
      </div>
      <div class="backend-empty" id="diary-no-results" hidden>No encontramos entradas con ese texto.</div>
    </section>
  </section>
</main>
<?php echo $__env->make('partials.floating-auxilio', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
(function(){
  const input = document.getElementById('diary-filter');
  const entries = Array.from(document.querySelectorAll('.diary-entry'));
  const empty = document.getElementById('diary-no-results');
  const normalize = (value) => (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

  function applyFilter() {
    const query = normalize(input?.value);
    let visible = 0;
    entries.forEach((entry, index) => {
      const match = !query || normalize(entry.dataset.search).includes(query);
      const withinLimit = query || index < 5;
      const show = match && withinLimit;
      entry.hidden = !show;
      if (show) visible++;
    });
    if (empty) empty.hidden = query === '' || visible > 0;
  }

  input?.addEventListener('input', applyFilter);
  applyFilter();
})();
</script>
</body></html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/paciente/diario-paciente.blade.php ENDPATH**/ ?>