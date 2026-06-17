<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis tareas | IRIS</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/auxilio.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/tareas.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-paciente.js')); ?>"></script>

<main class="dashboard-main tasks-page">
    <?php echo $__env->make('partials.paciente-header', ['title'=>'Mis tareas', 'subtitle'=>'Selecciona una tarea para entregar tu respuesta. Tu especialista podrá revisarla.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="tasks-shell">
        <div class="tasks-tabs" role="tablist" aria-label="Estado de tareas">
            <button class="task-tab active" type="button" data-task-tab="pending">
                Pendientes <span><?php echo e($pendingTasks->count()); ?></span>
            </button>
            <button class="task-tab" type="button" data-task-tab="completed">
                Completadas <span><?php echo e($completedTasks->count()); ?></span>
            </button>
        </div>

        <div class="task-tab-panel active" data-task-panel="pending">
            <?php $__empty_1 = true; $__currentLoopData = $pendingTasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $specialist = $task->professional;
                    $needsChanges = $task->status === 'requiere_cambios';
                ?>
                <article class="task-card-premium compact <?php echo e($needsChanges ? 'border-orange' : 'border-green'); ?>" data-task-card>

                    <div class="task-content-premium">
                        <span class="task-badge <?php echo e($needsChanges ? 'badge-orange' : 'badge-green'); ?>">
                            <?php echo e($needsChanges ? 'Requiere cambios' : 'Pendiente'); ?>

                        </span>
                        <h3><?php echo e($task->title); ?></h3>
                        <p><?php echo e($task->description ?: 'Tu especialista no agregó descripción adicional.'); ?></p>
                        <div class="task-author">
                            <span class="avatar-tiny"><?php echo e($specialist?->initials() ?? 'IR'); ?></span>
                            <span>Asignada por <?php echo e($specialist?->nombre_completo ?? 'Especialista no disponible'); ?></span>
                        </div>
                        <div class="task-meta-row">
                            <?php if($task->due_date): ?>
                                <span class="meta-pill">Entrega: <?php echo e($task->due_date->format('d/m/Y')); ?></span>
                            <?php endif; ?>
                            <?php if($task->repeat): ?>
                                <span class="meta-pill"><?php echo e($task->repeat); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if($needsChanges && $task->review_feedback): ?>
                            <div class="review-feedback"><strong>Comentarios del especialista:</strong> <?php echo e($task->review_feedback); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="task-action-panel">
                        <button class="task-select-btn" type="button" data-select-task>
                            <span class="select-icon">☑</span>
                            Seleccionar tarea
                        </button>

                        <form class="task-evidence-panel" method="POST" action="<?php echo e(route('paciente.mis-tareas.complete', $task)); ?>" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <p class="evidence-hint">Puedes entregar una respuesta escrita, adjuntar un PDF, o ambas cosas.</p>

                            <div class="evidence-fields">
                                <label class="field-label" for="follow_up_<?php echo e($task->id); ?>">Respuesta escrita</label>
                                <textarea id="follow_up_<?php echo e($task->id); ?>" class="evidence-textarea" name="follow_up" placeholder="Escribe aquí tu respuesta, reflexión o seguimiento..."><?php echo e(old('follow_up', $task->follow_up)); ?></textarea>

                                <div class="or-divider"><span>o adjunta evidencia</span></div>

                                <label class="field-label" for="evidence_pdf_<?php echo e($task->id); ?>">Archivo PDF</label>
                                <input id="evidence_pdf_<?php echo e($task->id); ?>" class="file-input" type="file" name="evidence_pdf" accept="application/pdf">
                                <?php if($task->evidence_file_path): ?>
                                    <a class="file-link" target="_blank" rel="noopener" href="<?php echo e(route('paciente.mis-tareas.pdf', $task)); ?>">
                                        Ver PDF actual: <?php echo e($task->evidence_file_name ?: 'evidencia.pdf'); ?>

                                    </a>
                                    <small class="evidence-hint">Si adjuntas un nuevo PDF, reemplazará al archivo actual.</small>
                                <?php endif; ?>
                            </div>

                            <button class="btn-primary btn-sm" type="submit">Completar tarea</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="backend-empty">No tienes tareas pendientes.</div>
            <?php endif; ?>
        </div>

        <div class="task-tab-panel" data-task-panel="completed">
            <?php $__empty_1 = true; $__currentLoopData = $completedTasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $specialist = $task->professional;
                    $submitted = $task->submitted_at?->format('d/m/Y H:i');
                    $approved = $task->isApproved();
                ?>
                <article class="task-card-premium compact border-green submitted-task">

                    <div class="task-content-premium">
                        <span class="task-badge <?php echo e($approved ? 'badge-green' : 'badge-orange'); ?>">
                            <?php echo e($approved ? 'Revisada' : 'En revisión'); ?>

                        </span>
                        <h3><?php echo e($task->title); ?></h3>
                        <p><?php echo e($task->description ?: 'Actividad terapéutica entregada.'); ?></p>
                        <div class="task-author">
                            <span class="avatar-tiny"><?php echo e($specialist?->initials() ?? 'IR'); ?></span>
                            <span>Asignada por <?php echo e($specialist?->nombre_completo ?? 'Especialista no disponible'); ?></span>
                        </div>
                        <div class="task-meta-row">
                            <?php if($submitted): ?><span class="meta-pill">Entregada: <?php echo e($submitted); ?></span><?php endif; ?>
                            <?php if($task->reviewed_at): ?><span class="meta-pill">Revisada: <?php echo e($task->reviewed_at->format('d/m/Y H:i')); ?></span><?php endif; ?>
                        </div>

                        <?php if($task->follow_up): ?>
                            <div class="submitted-answer"><strong>Tu respuesta:</strong><br><?php echo e($task->follow_up); ?></div>
                        <?php endif; ?>

                        <?php if($task->evidence_file_path): ?>
                            <a class="file-link" target="_blank" rel="noopener" href="<?php echo e(route('paciente.mis-tareas.pdf', $task)); ?>">
                                Ver PDF adjunto: <?php echo e($task->evidence_file_name ?: 'evidencia.pdf'); ?>

                            </a>
                        <?php endif; ?>

                        <?php if($task->review_feedback): ?>
                            <div class="review-feedback"><strong>Comentarios del especialista:</strong> <?php echo e($task->review_feedback); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="task-action-panel submitted-actions">
                        <?php if($task->canBeUnsubmitted()): ?>
                            <form method="POST" action="<?php echo e(route('paciente.mis-tareas.unsubmit', $task)); ?>">
                                <?php echo csrf_field(); ?>
                                <button class="btn-outline btn-sm" type="submit">Desentregar para modificar</button>
                            </form>
                        <?php else: ?>
                            <span class="meta-pill">Aprobada por tu especialista</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="backend-empty">Aún no has entregado tareas.</div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
document.querySelectorAll('[data-task-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.taskTab;
        document.querySelectorAll('[data-task-tab]').forEach((item) => item.classList.remove('active'));
        document.querySelectorAll('[data-task-panel]').forEach((panel) => panel.classList.remove('active'));
        tab.classList.add('active');
        document.querySelector(`[data-task-panel="${target}"]`)?.classList.add('active');
    });
});

document.querySelectorAll('[data-select-task]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('[data-task-card]');
        document.querySelectorAll('[data-task-card]').forEach((item) => {
            if (item !== card) item.classList.remove('selected');
        });
        card?.classList.toggle('selected');
        button.querySelector('.select-icon').textContent = card?.classList.contains('selected') ? '✓' : '☑';
    });
});
</script>
<?php echo $__env->make('partials.floating-auxilio', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/paciente/mis-tareas.blade.php ENDPATH**/ ?>