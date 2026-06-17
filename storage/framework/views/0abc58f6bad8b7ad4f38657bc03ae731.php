<!DOCTYPE html>
<html lang="es"><head><meta name="csrf-token" content="<?php echo e(csrf_token()); ?>"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Comunidad de Apoyo | IRIS</title><link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>"><link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>"><link rel="stylesheet" href="<?php echo e(asset('css/auxilio.css')); ?>"><link rel="stylesheet" href="<?php echo e(asset('css/comunidad.css')); ?>"><link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>"><link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>"></head>
<body class="dashboard-body community-page"><div id="menu-dinamico"></div><script src="<?php echo e(asset(auth()->user()->isPaciente() ? 'js/menu-paciente.js' : 'js/menu-psicologo.js')); ?>"></script><main class="dashboard-main">
<?php if(auth()->user()->isPaciente()): ?> <?php echo $__env->make('partials.paciente-header', ['title'=>'Comunidad de apoyo', 'subtitle'=>'Comparte y acompaña desde un espacio seguro y moderado.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php else: ?> <?php echo $__env->make('partials.profesional-header', ['title'=>'Comunidad de apoyo', 'subtitle'=>'Participa con orientación general sin sustituir una consulta clínica.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php endif; ?>
<?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<section class="community-hero-card">
  <div><span class="backend-chip">Espacio seguro</span><h2>Publicaciones de la comunidad</h2><p>Evita datos personales, diagnósticos de terceros o información de emergencia. Para crisis, usa Auxilio.</p></div>
  <a class="btn-outline" href="<?php echo e(route('comunidad.mis-publicaciones')); ?>">Mis publicaciones</a>
</section>

<section class="community-layout improved-community-layout">
  <div class="feed-column">
    <form class="create-post-card improved-create-post" method="POST" action="<?php echo e(route('comunidad.store')); ?>">
      <?php echo csrf_field(); ?>
      <div class="create-post-header">
        <span class="community-avatar current-user-avatar"><?php echo e(auth()->user()->initials()); ?></span>
        <div><strong>Comparte algo con la comunidad</strong><p>Tu publicación quedará registrada con tu cuenta, salvo que marques anonimato.</p></div>
      </div>
      <div class="backend-form-grid two">
        <label class="backend-field">Título<input name="title" maxlength="180" required placeholder="Ej. Algo que me ayudó esta semana"></label>
        <label class="backend-field">Categoría<select name="category"><option value="general">General</option><option value="ansiedad">Ansiedad</option><option value="habitos">Hábitos</option><option value="apoyo">Apoyo</option><option value="logros">Logros</option></select></label>
      </div>
      <label class="backend-field">Contenido<textarea name="content" required placeholder="Escribe tu publicación..."></textarea></label>
      <div class="create-post-actions"><label class="toggle-anonymous"><span class="switch-small"><input type="checkbox" name="anonymous" value="1"><span class="slider-small"></span></span><span class="toggle-text">Publicar de forma anónima</span></label><button class="btn-primary" type="submit">Publicar</button></div>
    </form>

    <div class="category-filter-row">
      <a class="backend-chip <?php echo e(request('category') ? 'muted' : ''); ?>" href="<?php echo e(route('comunidad.index')); ?>">Todo</a>
      <?php $__currentLoopData = ['general'=>'General','ansiedad'=>'Ansiedad','habitos'=>'Hábitos','apoyo'=>'Apoyo','logros'=>'Logros']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a class="backend-chip <?php echo e(request('category') === $key ? '' : 'muted'); ?>" href="<?php echo e(route('comunidad.index', ['category'=>$key])); ?>"><?php echo e($label); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <section class="community-feed">
      <?php $__empty_1 = true; $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $isAuthor = $post->user_id === auth()->id();
          $isLiked = $post->likes->contains('user_id', auth()->id());
          $displayName = $post->anonymous ? 'Usuario anónimo' : ($post->user?->nombre_completo ?? 'Usuario');
          $avatarText = $post->anonymous ? 'AN' : ($post->user?->initials() ?? 'IR');
        ?>
        <article class="post-card improved-post-card" id="post-<?php echo e($post->id); ?>">
          <div class="post-header">
            <div class="post-author"><span class="community-avatar <?php echo e($post->anonymous ? 'anonymous-avatar' : ''); ?>"><?php echo e($avatarText); ?></span><div class="author-info"><strong><?php echo e($displayName); ?></strong><span class="post-time"><?php echo e($post->created_at->diffForHumans()); ?> · <?php echo e(ucfirst($post->category ?? 'general')); ?></span><?php if(!$post->anonymous && $post->user?->isProfesional()): ?><span class="role-badge specialist">Profesional IRIS</span><?php endif; ?></div></div>
            <?php if($isAuthor): ?><span class="backend-chip muted">Tu publicación</span><?php endif; ?>
          </div>
          <div class="post-content"><h3><?php echo e($post->title); ?></h3><p><?php echo e($post->content); ?></p></div>
          <div class="post-interactions">
            <form class="inline-form" method="POST" action="<?php echo e(route('comunidad.like',$post)); ?>"><?php echo csrf_field(); ?><button class="interact-btn <?php echo e($isLiked ? 'active-reply' : ''); ?>" type="submit">💚 Me ayuda · <?php echo e($post->likes_count); ?></button></form>
            <span class="interact-btn">💬 Comentarios · <?php echo e($post->comments_count); ?></span>
            <?php if($isAuthor || auth()->user()->isAdmin()): ?><form class="inline-form" method="POST" action="<?php echo e(route('comunidad.destroy',$post)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="interact-btn danger-action" onclick="return confirm('¿Eliminar publicación?')">Eliminar</button></form><?php endif; ?>
          </div>
          <div class="comments-section">
            <?php $__empty_2 = true; $__currentLoopData = $post->comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
              <?php $commentName = $comment->anonymous ? 'Anónimo' : ($comment->user?->nombre_completo ?? 'Usuario'); $commentAvatar = $comment->anonymous ? 'AN' : ($comment->user?->initials() ?? 'IR'); ?>
              <div class="comment-item <?php echo e($comment->user?->isProfesional() ? 'professional-reply' : ''); ?>"><span class="community-avatar comment-avatar <?php echo e($comment->anonymous ? 'anonymous-avatar' : ''); ?>"><?php echo e($commentAvatar); ?></span><div class="comment-body"><div class="comment-author"><strong><?php echo e($commentName); ?></strong><small><?php echo e($comment->created_at->diffForHumans()); ?></small><?php if($comment->user?->isProfesional()): ?><span class="role-badge specialist">Profesional</span><?php endif; ?></div><p><?php echo e($comment->content); ?></p><?php if($comment->user_id === auth()->id() || auth()->user()->isAdmin()): ?><form method="POST" action="<?php echo e(route('comunidad.comment.destroy',$comment)); ?>" class="comment-delete-form"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn-outline btn-sm">Eliminar comentario</button></form><?php endif; ?></div></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
              <p class="comment-empty">Aún no hay comentarios. Sé la primera persona en responder con respeto.</p>
            <?php endif; ?>
            <form class="reply-input-wrapper" method="POST" action="<?php echo e(route('comunidad.comment',$post)); ?>"><?php echo csrf_field(); ?><span class="community-avatar comment-avatar"><?php echo e(auth()->user()->initials()); ?></span><input class="reply-input" name="content" required placeholder="Escribe un comentario respetuoso..."><label class="mini-anonymous"><input type="checkbox" name="anonymous" value="1"> Anónimo</label><button class="send-reply-btn" type="submit" aria-label="Comentar">➜</button></form>
          </div>
          <form class="report-form" method="POST" action="<?php echo e(route('comunidad.report',$post)); ?>"><?php echo csrf_field(); ?>
            <details>
              <summary>Reportar publicación</summary>
              <div class="report-grid">
                <label class="backend-field">Motivo<input name="reason" maxlength="120" required placeholder="Ej. lenguaje inapropiado"></label>
                <label class="backend-field">Detalles<textarea name="details" rows="3" placeholder="Describe brevemente el problema."></textarea></label>
              </div>
              <button class="btn-outline btn-sm" type="submit">Enviar reporte</button>
            </details>
          </form>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="backend-empty">Todavía no hay publicaciones. Crea la primera desde el formulario superior.</div>
      <?php endif; ?>
    </section>
    <?php echo e($posts->links()); ?>

  </div>

  <aside class="info-column">
    <div class="side-card"><h3>Reglas básicas</h3><p>No publiques datos personales, expedientes, direcciones, teléfonos ni información de terceros. <a href="<?php echo e(url('/legal/view/reglas_comunidad')); ?>" target="_blank">Ver reglas completas</a>.</p></div>
    <div class="side-card"><h3>Moderación</h3><p>Las publicaciones reportadas llegan al administrador para revisión.</p></div>
    <div class="side-card"><h3>Recordatorio</h3><p>La comunidad acompaña, pero no reemplaza atención profesional o servicios de emergencia.</p></div>
  </aside>
</section>
</main><?php echo $__env->make('partials.floating-auxilio', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></body></html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/comunidad/comunidad.blade.php ENDPATH**/ ?>