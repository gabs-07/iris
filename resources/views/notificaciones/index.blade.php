<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificaciones | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body class="dashboard-body">
<main class="dashboard-main" style="max-width:900px;margin:auto;padding:2rem;">
  <h1>Notificaciones</h1>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-outline">Marcar todas como leídas</button></form>
  <div style="display:grid;gap:1rem;margin-top:1rem;">
    @forelse($notifications as $notification)
      <article class="dashboard-card" style="padding:1rem;{{ $notification->read_at ? 'opacity:.7' : '' }}">
        <h3>{{ $notification->data['title'] ?? 'Notificación' }}</h3>
        <p>{{ $notification->data['message'] ?? '' }}</p>
        <small>{{ $notification->created_at->diffForHumans() }}</small><br>
        <a class="btn-primary-small" href="{{ route('notifications.read', $notification->id) }}">Abrir</a>
      </article>
    @empty
      <p>No tienes notificaciones.</p>
    @endforelse
  </div>
  {{ $notifications->links() }}
</main>
</body>
</html>
