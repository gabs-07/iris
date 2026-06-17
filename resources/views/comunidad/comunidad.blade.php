<!DOCTYPE html>
<html lang="es"><head><meta name="csrf-token" content="{{ csrf_token() }}"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Comunidad de Apoyo | IRIS</title><link rel="stylesheet" href="{{ asset('css/global.css') }}"><link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"><link rel="stylesheet" href="{{ asset('css/auxilio.css') }}"><link rel="stylesheet" href="{{ asset('css/comunidad.css') }}"><link rel="stylesheet" href="{{ asset('css/sidebar.css') }}"><link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}"></head>
<body class="dashboard-body community-page"><div id="menu-dinamico"></div><script src="{{ asset(auth()->user()->isPaciente() ? 'js/menu-paciente.js' : 'js/menu-psicologo.js') }}"></script><main class="dashboard-main">
@if(auth()->user()->isPaciente()) @include('partials.paciente-header', ['title'=>'Comunidad de apoyo', 'subtitle'=>'Comparte y acompaña desde un espacio seguro y moderado.']) @else @include('partials.profesional-header', ['title'=>'Comunidad de apoyo', 'subtitle'=>'Participa con orientación general sin sustituir una consulta clínica.']) @endif
@include('partials.frontend-alerts')

<section class="community-hero-card">
  <div><span class="backend-chip">Espacio seguro</span><h2>Publicaciones de la comunidad</h2><p>Evita datos personales, diagnósticos de terceros o información de emergencia. Para crisis, usa Auxilio.</p></div>
  <a class="btn-outline" href="{{ route('comunidad.mis-publicaciones') }}">Mis publicaciones</a>
</section>

<section class="community-layout improved-community-layout">
  <div class="feed-column">
    <form class="create-post-card improved-create-post" method="POST" action="{{ route('comunidad.store') }}">
      @csrf
      <div class="create-post-header">
        <span class="community-avatar current-user-avatar">{{ auth()->user()->initials() }}</span>
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
      <a class="backend-chip {{ request('category') ? 'muted' : '' }}" href="{{ route('comunidad.index') }}">Todo</a>
      @foreach(['general'=>'General','ansiedad'=>'Ansiedad','habitos'=>'Hábitos','apoyo'=>'Apoyo','logros'=>'Logros'] as $key=>$label)
        <a class="backend-chip {{ request('category') === $key ? '' : 'muted' }}" href="{{ route('comunidad.index', ['category'=>$key]) }}">{{ $label }}</a>
      @endforeach
    </div>

    <section class="community-feed">
      @forelse($posts as $post)
        @php
          $isAuthor = $post->user_id === auth()->id();
          $isLiked = $post->likes->contains('user_id', auth()->id());
          $displayName = $post->anonymous ? 'Usuario anónimo' : ($post->user?->nombre_completo ?? 'Usuario');
          $avatarText = $post->anonymous ? 'AN' : ($post->user?->initials() ?? 'IR');
        @endphp
        <article class="post-card improved-post-card" id="post-{{ $post->id }}">
          <div class="post-header">
            <div class="post-author"><span class="community-avatar {{ $post->anonymous ? 'anonymous-avatar' : '' }}">{{ $avatarText }}</span><div class="author-info"><strong>{{ $displayName }}</strong><span class="post-time">{{ $post->created_at->diffForHumans() }} · {{ ucfirst($post->category ?? 'general') }}</span>@if(!$post->anonymous && $post->user?->isProfesional())<span class="role-badge specialist">Profesional IRIS</span>@endif</div></div>
            @if($isAuthor)<span class="backend-chip muted">Tu publicación</span>@endif
          </div>
          <div class="post-content"><h3>{{ $post->title }}</h3><p>{{ $post->content }}</p></div>
          <div class="post-interactions">
            <form class="inline-form" method="POST" action="{{ route('comunidad.like',$post) }}">@csrf<button class="interact-btn {{ $isLiked ? 'active-reply' : '' }}" type="submit">💚 Me ayuda · {{ $post->likes_count }}</button></form>
            <span class="interact-btn">💬 Comentarios · {{ $post->comments_count }}</span>
            @if($isAuthor || auth()->user()->isAdmin())<form class="inline-form" method="POST" action="{{ route('comunidad.destroy',$post) }}">@csrf @method('DELETE')<button class="interact-btn danger-action" onclick="return confirm('¿Eliminar publicación?')">Eliminar</button></form>@endif
          </div>
          <div class="comments-section">
            @forelse($post->comments as $comment)
              @php $commentName = $comment->anonymous ? 'Anónimo' : ($comment->user?->nombre_completo ?? 'Usuario'); $commentAvatar = $comment->anonymous ? 'AN' : ($comment->user?->initials() ?? 'IR'); @endphp
              <div class="comment-item {{ $comment->user?->isProfesional() ? 'professional-reply' : '' }}"><span class="community-avatar comment-avatar {{ $comment->anonymous ? 'anonymous-avatar' : '' }}">{{ $commentAvatar }}</span><div class="comment-body"><div class="comment-author"><strong>{{ $commentName }}</strong><small>{{ $comment->created_at->diffForHumans() }}</small>@if($comment->user?->isProfesional())<span class="role-badge specialist">Profesional</span>@endif</div><p>{{ $comment->content }}</p>@if($comment->user_id === auth()->id() || auth()->user()->isAdmin())<form method="POST" action="{{ route('comunidad.comment.destroy',$comment) }}" class="comment-delete-form">@csrf @method('DELETE')<button class="btn-outline btn-sm">Eliminar comentario</button></form>@endif</div></div>
            @empty
              <p class="comment-empty">Aún no hay comentarios. Sé la primera persona en responder con respeto.</p>
            @endforelse
            <form class="reply-input-wrapper" method="POST" action="{{ route('comunidad.comment',$post) }}">@csrf<span class="community-avatar comment-avatar">{{ auth()->user()->initials() }}</span><input class="reply-input" name="content" required placeholder="Escribe un comentario respetuoso..."><label class="mini-anonymous"><input type="checkbox" name="anonymous" value="1"> Anónimo</label><button class="send-reply-btn" type="submit" aria-label="Comentar">➜</button></form>
          </div>
          <form class="report-form" method="POST" action="{{ route('comunidad.report',$post) }}">@csrf
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
      @empty
        <div class="backend-empty">Todavía no hay publicaciones. Crea la primera desde el formulario superior.</div>
      @endforelse
    </section>
    {{ $posts->links() }}
  </div>

  <aside class="info-column">
    <div class="side-card"><h3>Reglas básicas</h3><p>No publiques datos personales, expedientes, direcciones, teléfonos ni información de terceros. <a href="{{ url('/legal/view/reglas_comunidad') }}" target="_blank">Ver reglas completas</a>.</p></div>
    <div class="side-card"><h3>Moderación</h3><p>Las publicaciones reportadas llegan al administrador para revisión.</p></div>
    <div class="side-card"><h3>Recordatorio</h3><p>La comunidad acompaña, pero no reemplaza atención profesional o servicios de emergencia.</p></div>
  </aside>
</section>
</main>@include('partials.floating-auxilio')</body></html>
