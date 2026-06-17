<!DOCTYPE html>
<html lang="es"><head><meta name="csrf-token" content="{{ csrf_token() }}"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mis publicaciones | IRIS</title><link rel="stylesheet" href="{{ asset('css/global.css') }}"><link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"><link rel="stylesheet" href="{{ asset('css/comunidad.css') }}"><link rel="stylesheet" href="{{ asset('css/sidebar.css') }}"><link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}"></head>
<body class="dashboard-body community-page"><div id="menu-dinamico"></div><script src="{{ asset(auth()->user()->isPaciente() ? 'js/menu-paciente.js' : 'js/menu-psicologo.js') }}"></script><main class="dashboard-main">
@if(auth()->user()->isPaciente()) @include('partials.paciente-header', ['title'=>'Mis publicaciones', 'subtitle'=>'Administra lo que has compartido en comunidad.']) @else @include('partials.profesional-header', ['title'=>'Mis publicaciones', 'subtitle'=>'Administra lo que has compartido en comunidad.']) @endif
@include('partials.frontend-alerts')
<div class="community-hero-card"><div><span class="backend-chip">Gestión personal</span><h2>Editar publicaciones</h2><p>Actualiza o elimina tus publicaciones reales.</p></div><a class="btn-primary" href="{{ route('comunidad.index') }}">Volver a comunidad</a></div>
<section class="community-feed my-posts-feed">
@forelse($posts as $post)
  <article class="post-card improved-post-card editable-post-card">
    <div class="post-header"><div class="post-author"><span class="community-avatar">{{ auth()->user()->initials() }}</span><div class="author-info"><strong>{{ $post->anonymous ? 'Publicación anónima' : auth()->user()->nombre_completo }}</strong><span class="post-time">{{ $post->created_at->diffForHumans() }} · {{ ucfirst($post->category ?? 'general') }}</span></div></div><span class="backend-chip muted">{{ $post->likes_count }} reacciones · {{ $post->comments_count }} comentarios</span></div>
    <form id="edit-post-{{ $post->id }}" method="POST" action="{{ route('comunidad.update',$post) }}" class="edit-post-form">@csrf @method('PUT')
      <div class="backend-form-grid two"><label class="backend-field">Título<input name="title" value="{{ $post->title }}" required></label><label class="backend-field">Categoría<select name="category"><option value="general" @selected($post->category==='general')>General</option><option value="ansiedad" @selected($post->category==='ansiedad')>Ansiedad</option><option value="habitos" @selected($post->category==='habitos')>Hábitos</option><option value="apoyo" @selected($post->category==='apoyo')>Apoyo</option><option value="logros" @selected($post->category==='logros')>Logros</option></select></label></div>
      <label class="backend-field">Contenido<textarea name="content" required>{{ $post->content }}</textarea></label>
      <label class="toggle-anonymous"><span class="switch-small"><input type="checkbox" name="anonymous" value="1" @checked($post->anonymous)><span class="slider-small"></span></span><span class="toggle-text">Mantener anónima</span></label>
    </form>
    <div class="backend-actions"><button class="btn-primary btn-sm" form="edit-post-{{ $post->id }}">Guardar cambios</button><form method="POST" action="{{ route('comunidad.destroy',$post) }}">@csrf @method('DELETE')<button class="btn-outline btn-sm" onclick="return confirm('¿Eliminar publicación?')">Eliminar</button></form></div>
  </article>
@empty<div class="backend-empty">No tienes publicaciones registradas.</div>@endforelse
</section>{{ $posts->links() }}</main></body></html>
