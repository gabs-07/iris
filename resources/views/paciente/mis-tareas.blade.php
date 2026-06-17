<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis tareas | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tareas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>

<main class="dashboard-main tasks-page">
    @include('partials.paciente-header', ['title'=>'Mis tareas', 'subtitle'=>'Selecciona una tarea para entregar tu respuesta. Tu especialista podrá revisarla.'])
    @include('partials.frontend-alerts')

    <section class="tasks-shell">
        <div class="tasks-tabs" role="tablist" aria-label="Estado de tareas">
            <button class="task-tab active" type="button" data-task-tab="pending">
                Pendientes <span>{{ $pendingTasks->count() }}</span>
            </button>
            <button class="task-tab" type="button" data-task-tab="completed">
                Completadas <span>{{ $completedTasks->count() }}</span>
            </button>
        </div>

        <div class="task-tab-panel active" data-task-panel="pending">
            @forelse($pendingTasks as $task)
                @php
                    $specialist = $task->professional;
                    $needsChanges = $task->status === 'requiere_cambios';
                @endphp
                <article class="task-card-premium compact {{ $needsChanges ? 'border-orange' : 'border-green' }}" data-task-card>

                    <div class="task-content-premium">
                        <span class="task-badge {{ $needsChanges ? 'badge-orange' : 'badge-green' }}">
                            {{ $needsChanges ? 'Requiere cambios' : 'Pendiente' }}
                        </span>
                        <h3>{{ $task->title }}</h3>
                        <p>{{ $task->description ?: 'Tu especialista no agregó descripción adicional.' }}</p>
                        <div class="task-author">
                            <span class="avatar-tiny">{{ $specialist?->initials() ?? 'IR' }}</span>
                            <span>Asignada por {{ $specialist?->nombre_completo ?? 'Especialista no disponible' }}</span>
                        </div>
                        <div class="task-meta-row">
                            @if($task->due_date)
                                <span class="meta-pill">Entrega: {{ $task->due_date->format('d/m/Y') }}</span>
                            @endif
                            @if($task->repeat)
                                <span class="meta-pill">{{ $task->repeat }}</span>
                            @endif
                        </div>
                        @if($needsChanges && $task->review_feedback)
                            <div class="review-feedback"><strong>Comentarios del especialista:</strong> {{ $task->review_feedback }}</div>
                        @endif
                    </div>

                    <div class="task-action-panel">
                        <button class="task-select-btn" type="button" data-select-task>
                            <span class="select-icon">☑</span>
                            Seleccionar tarea
                        </button>

                        <form class="task-evidence-panel" method="POST" action="{{ route('paciente.mis-tareas.complete', $task) }}" enctype="multipart/form-data">
                            @csrf
                            <p class="evidence-hint">Puedes entregar una respuesta escrita, adjuntar un PDF, o ambas cosas.</p>

                            <div class="evidence-fields">
                                <label class="field-label" for="follow_up_{{ $task->id }}">Respuesta escrita</label>
                                <textarea id="follow_up_{{ $task->id }}" class="evidence-textarea" name="follow_up" placeholder="Escribe aquí tu respuesta, reflexión o seguimiento...">{{ old('follow_up', $task->follow_up) }}</textarea>

                                <div class="or-divider"><span>o adjunta evidencia</span></div>

                                <label class="field-label" for="evidence_pdf_{{ $task->id }}">Archivo PDF</label>
                                <input id="evidence_pdf_{{ $task->id }}" class="file-input" type="file" name="evidence_pdf" accept="application/pdf">
                                @if($task->evidence_file_path)
                                    <a class="file-link" target="_blank" rel="noopener" href="{{ route('paciente.mis-tareas.pdf', $task) }}">
                                        Ver PDF actual: {{ $task->evidence_file_name ?: 'evidencia.pdf' }}
                                    </a>
                                    <small class="evidence-hint">Si adjuntas un nuevo PDF, reemplazará al archivo actual.</small>
                                @endif
                            </div>

                            <button class="btn-primary btn-sm" type="submit">Completar tarea</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="backend-empty">No tienes tareas pendientes.</div>
            @endforelse
        </div>

        <div class="task-tab-panel" data-task-panel="completed">
            @forelse($completedTasks as $task)
                @php
                    $specialist = $task->professional;
                    $submitted = $task->submitted_at?->format('d/m/Y H:i');
                    $approved = $task->isApproved();
                @endphp
                <article class="task-card-premium compact border-green submitted-task">

                    <div class="task-content-premium">
                        <span class="task-badge {{ $approved ? 'badge-green' : 'badge-orange' }}">
                            {{ $approved ? 'Revisada' : 'En revisión' }}
                        </span>
                        <h3>{{ $task->title }}</h3>
                        <p>{{ $task->description ?: 'Actividad terapéutica entregada.' }}</p>
                        <div class="task-author">
                            <span class="avatar-tiny">{{ $specialist?->initials() ?? 'IR' }}</span>
                            <span>Asignada por {{ $specialist?->nombre_completo ?? 'Especialista no disponible' }}</span>
                        </div>
                        <div class="task-meta-row">
                            @if($submitted)<span class="meta-pill">Entregada: {{ $submitted }}</span>@endif
                            @if($task->reviewed_at)<span class="meta-pill">Revisada: {{ $task->reviewed_at->format('d/m/Y H:i') }}</span>@endif
                        </div>

                        @if($task->follow_up)
                            <div class="submitted-answer"><strong>Tu respuesta:</strong><br>{{ $task->follow_up }}</div>
                        @endif

                        @if($task->evidence_file_path)
                            <a class="file-link" target="_blank" rel="noopener" href="{{ route('paciente.mis-tareas.pdf', $task) }}">
                                Ver PDF adjunto: {{ $task->evidence_file_name ?: 'evidencia.pdf' }}
                            </a>
                        @endif

                        @if($task->review_feedback)
                            <div class="review-feedback"><strong>Comentarios del especialista:</strong> {{ $task->review_feedback }}</div>
                        @endif
                    </div>

                    <div class="task-action-panel submitted-actions">
                        @if($task->canBeUnsubmitted())
                            <form method="POST" action="{{ route('paciente.mis-tareas.unsubmit', $task) }}">
                                @csrf
                                <button class="btn-outline btn-sm" type="submit">Desentregar para modificar</button>
                            </form>
                        @else
                            <span class="meta-pill">Aprobada por tu especialista</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="backend-empty">Aún no has entregado tareas.</div>
            @endforelse
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
@include('partials.floating-auxilio')
</body>
</html>
