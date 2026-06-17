@php
    $agendaEvents = $appointments->map(fn ($appointment) => [
        'id' => $appointment->id,
        'folio' => (string) $appointment->id,
        'patient' => $appointment->patient?->nombre_completo ?? 'Paciente no disponible',
        'title' => (string) $appointment->reason,
        'modality' => (string) $appointment->modality,
        'status' => (string) $appointment->status,
        'date' => optional($appointment->starts_at)->format('Y-m-d'),
        'time' => optional($appointment->starts_at)->format('H:i'),
        'displayDate' => optional($appointment->starts_at)->translatedFormat('j F Y, H:i'),
        'room_link' => $appointment->professional_video_url,
        'session_available' => $appointment->is_video_session_available ?? false,
        'session_ends_at' => optional($appointment->session_access_ends_at)->format('H:i'),
    ])->values();
    $allRequestCards = $pendingRequests->concat($resolvedRequests ?? collect())->values();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-role" content="{{ auth()->user()->rol }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Agenda profesional | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/fontawesome-local.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/agenda.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-psicologo.js') }}"></script>

<main class="dashboard-main">
    <header class="dashboard-header agenda-header">
        <div class="header-copy">
            <div class="welcome-text">
                <h1 id="view-title">Agenda semanal</h1>
                <p>Gestiona tus sesiones, horarios, solicitudes y notas clínicas.</p>
            </div>
        </div>
        <div class="header-actions">
            @include('partials.notification-modal')
            <div class="profile-chip"><span class="avatar">{{ auth()->user()->initials() }}</span><div class="profile-info"><strong>{{ auth()->user()->nombre_completo }}</strong><span>{{ auth()->user()->professionalProfile?->especialidad_principal ?: ucfirst(auth()->user()->rol) }}</span></div></div>
        </div>
    </header>

    @include('partials.frontend-alerts')

    <section class="agenda-summary-grid">
        <article class="metric-card"><div class="metric-header"><span>Sesiones programadas</span><strong id="total-sessions">{{ $appointments->where('status', 'accepted')->count() }}</strong></div><p>En el período actual</p></article>
        <article class="metric-card"><div class="metric-header"><span>Solicitudes pendientes</span><strong>{{ $pendingRequests->count() }}</strong></div><p>Listas para aceptar, rechazar o reagendar</p></article>
        <article class="metric-card"><div class="metric-header"><span>Pacientes vinculados</span><strong>{{ $patients->count() }}</strong></div><p>Con atención activa o histórica</p></article>
    </section>

    <section class="agenda-board-grid">
        <div class="agenda-calendar-card">
            <div class="agenda-toolbar">
                <div><p class="agenda-date-label" id="date-label">{{ now()->translatedFormat('F Y') }}</p><h2 id="toolbar-title">Vista semanal</h2></div>
                <div class="agenda-toolbar-actions"><button id="open-request-appointment" class="btn-primary btn-sm" type="button">Solicitar cita</button><button id="prev-btn" class="btn-outline btn-sm" type="button">← Anterior</button><button id="next-btn" class="btn-outline btn-sm" type="button">Siguiente →</button></div>
            </div>
            <div class="agenda-bottom-toolbar">
                <button id="today-btn-bottom" class="btn-outline btn-sm" type="button">Hoy</button>
                <div class="agenda-view-toggle-bottom"><button type="button" data-view="day">Día</button><button type="button" data-view="week" class="active">Semana</button><button type="button" data-view="month">Mes</button></div>
            </div>
            <div class="agenda-calendar-content"><div class="agenda-view-content"><div id="week-view" class="agenda-week-view active"></div><div id="day-view" class="agenda-day-view"></div><div id="month-view" class="agenda-month-view"></div></div></div>
        </div>

        <aside class="agenda-info-card">
            <div class="info-header"><h2>Detalle de sesión</h2><p class="info-sub">Selecciona una cita del calendario para consultar solo sus datos.</p></div>
            <div class="agenda-detail-card" id="detail-card">
                <div class="detail-empty" id="detail-empty"><span>📅</span><h3 id="detail-title">Ninguna sesión seleccionada</h3><p id="detail-meta">Selecciona una cita para ver la información.</p></div>
                <div class="detail-content" id="detail-content" hidden>
                    <h3 id="selected-title"></h3>
                    <p id="selected-patient"></p>
                    <div class="detail-row"><strong>Fecha</strong><span id="detail-date"></span></div>
                    <div class="detail-row"><strong>Modalidad</strong><span id="detail-modality"></span></div>
                    <div class="detail-row"><strong>Estado</strong><span id="detail-status"></span></div>
                    <div class="detail-row detail-session-action"><a id="detail-session-link" class="btn-primary btn-sm" href="{{ route('profesional.sesion') }}">Entrar a sesión</a></div>
                </div>
            </div>
        </aside>
    </section>

    <section class="psych-request-inbox" id="solicitudes" aria-labelledby="psych-request-title">
        <div class="request-inbox-header">
            <div><p class="section-kicker"><i class="fas fa-inbox"></i> Gestión de solicitudes</p><h2 id="psych-request-title">Bandeja de solicitudes de cita</h2><p>Centraliza las citas nuevas y las solicitudes de reagenda antes de confirmar cualquier cambio.</p></div>
            <div class="request-inbox-stats" aria-label="Resumen de solicitudes"><article><strong>{{ $requestStats['new'] }}</strong><span>Nuevas</span></article><article><strong>{{ $requestStats['reschedule'] }}</strong><span>Reagendas</span></article><article><strong>{{ $requestStats['resolved'] }}</strong><span>Resueltas</span></article></div>
        </div>

        <div class="psych-request-tabs" role="tablist" aria-label="Filtrar solicitudes de cita">
            <button class="psych-request-tab active" type="button" data-psych-request-filter="all" aria-selected="true">Todas</button>
            <button class="psych-request-tab" type="button" data-psych-request-filter="new" aria-selected="false">Nuevas citas</button>
            <button class="psych-request-tab" type="button" data-psych-request-filter="reschedule" aria-selected="false">Reagendas</button>
            <button class="psych-request-tab" type="button" data-psych-request-filter="resolved" aria-selected="false">Resueltas</button>
        </div>

        <div class="psych-request-list" id="psych-request-list">
            @forelse($allRequestCards as $request)
                @php($isReagenda = filled($request->reschedule_proposal))
                @php($isResolved = in_array($request->status, ['accepted','rejected','cancelled','completed','missed'], true))
                <article class="psych-request-card" data-request-card data-psych-request-type="{{ $isResolved ? 'resolved' : ($isReagenda ? 'reschedule' : 'new') }}">
                    <div class="request-date-box"><span>{{ optional($request->starts_at)->translatedFormat('M') }}</span><strong>{{ optional($request->starts_at)->format('d') }}</strong><small>{{ optional($request->starts_at)->format('Y') }}</small></div>
                    <div class="request-card-body">
                        <div class="request-card-top"><span class="request-status {{ $isResolved ? 'status-resolved' : ($isReagenda ? 'status-reschedule' : 'status-new') }}">{{ $isResolved ? 'Resuelta' : ($isReagenda ? 'Reagenda' : 'Nueva cita') }}</span><span class="request-hour">{{ optional($request->starts_at)->format('H:i') }} hrs</span><span class="request-code">ID {{ $request->id }}</span></div>
                        <h3>{{ $request->patient?->nombre_completo ?? 'Paciente no disponible' }}</h3>
                        <p>{{ $request->notes ?: $request->reason }}</p>
                        <div class="request-card-meta"><span>Modalidad: {{ $request->modality }}</span><span>Motivo: {{ $request->reason }}</span></div>
                    </div>
                    <div class="request-card-actions request-action-stack">
                        @if(!$isResolved)
                            <form method="POST" action="{{ route('profesional.solicitudes.update', $request) }}">@csrf<input type="hidden" name="action" value="accepted"><input type="url" name="room_link" placeholder="Zoom se genera automático; enlace opcional" class="form-input"><button class="btn-primary-small" type="submit">Aceptar</button></form>
                            <details class="inline-reschedule"><summary class="btn-outline btn-sm">Proponer otro horario</summary><form method="POST" action="{{ route('profesional.solicitudes.update', $request) }}">@csrf<input type="hidden" name="action" value="rescheduled"><textarea name="reschedule_proposal" required placeholder="Mensaje para el paciente" class="form-input"></textarea><input type="date" name="reschedule_date" required class="form-input"><input type="time" name="reschedule_time" required class="form-input"><button class="btn-outline btn-sm" type="submit">Enviar propuesta</button></form></details>
                            <details class="inline-reschedule"><summary class="btn-outline request-danger btn-sm">Rechazar</summary><form method="POST" action="{{ route('profesional.solicitudes.update', $request) }}">@csrf<input type="hidden" name="action" value="rejected"><textarea name="cancel_reason" required placeholder="Motivo del rechazo" class="form-input"></textarea><button class="btn-outline request-danger btn-sm" type="submit">Confirmar rechazo</button></form></details>
                        @else
                            <span class="item-status">{{ $request->status === 'missed' ? 'Perdida / sin entrar' : ucfirst(str_replace('_',' ', $request->status)) }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="request-empty-state" id="psych-request-empty"><i class="fas fa-check-circle"></i><h3>No hay solicitudes pendientes</h3><p>Cuando existan nuevas citas o reagendas aparecerán aquí.</p></div>
            @endforelse
        </div>
    </section>
</main>
<div class="iris-action-modal-backdrop" id="agenda-request-modal" hidden>
    <section class="iris-action-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-request-title">
        <button class="iris-modal-close" type="button" data-close-agenda-request aria-label="Cerrar">×</button>
        <p class="section-kicker"><i class="fas fa-calendar-plus"></i> Solicitar cita al paciente</p>
        <h2 id="agenda-request-title">Nueva solicitud desde agenda</h2>
        <p class="muted-copy">El paciente recibirá la solicitud para confirmar la cita. Puedes elegir día, semana o mes desde el calendario.</p>
        <form method="POST" action="{{ route('profesional.agenda.store') }}" class="modal-form-grid">
            @csrf
            <input type="hidden" name="agenda_action" value="request_to_patient">
            <label>Paciente
                <select name="patient_id" required>
                    <option value="">Selecciona paciente</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}">{{ $patient->nombre_completo }} · {{ $patient->email }}</option>
                    @endforeach
                </select>
            </label>
            <label>Motivo
                <input name="title" required maxlength="180" placeholder="Seguimiento, evaluación, revisión de avances...">
            </label>
            <div class="form-row two">
                <label>Fecha<input id="agenda-request-date" type="date" name="date" required min="{{ today()->toDateString() }}"></label>
                <label>Hora<input id="agenda-request-time" type="time" name="time" required></label>
            </div>
            <label>Modalidad
                <select name="modality" required>
                    <option>Videollamada</option>
                    <option>Llamada</option>
                    <option>Presencial</option>
                </select>
            </label>
            <label>Enlace de sesión opcional<input type="url" name="room_link" placeholder="Zoom se genera automáticamente si lo dejas vacío"></label>
            <label>Mensaje para el paciente<textarea name="notes" rows="4" placeholder="Explica brevemente por qué solicitas esta cita."></textarea></label>
            <div class="modal-actions"><button class="btn-outline" type="button" data-close-agenda-request>Cancelar</button><button class="btn-primary" type="submit">Enviar solicitud</button></div>
        </form>
    </section>
</div>
<script>
const agendaEvents = @json($agendaEvents);
let currentDate = new Date();
let currentView = 'week';
const agendaRequestModal = document.getElementById('agenda-request-modal');
const agendaRequestDate = document.getElementById('agenda-request-date');
const agendaRequestTime = document.getElementById('agenda-request-time');
function openAgendaRequestModal(dateValue = null, timeValue = '09:00') {
  if (dateValue && agendaRequestDate) agendaRequestDate.value = dateValue;
  if (timeValue && agendaRequestTime) agendaRequestTime.value = timeValue;
  agendaRequestModal?.removeAttribute('hidden');
}
function closeAgendaRequestModal() { agendaRequestModal?.setAttribute('hidden', 'hidden'); }
document.getElementById('open-request-appointment')?.addEventListener('click', () => openAgendaRequestModal(dateKey(currentDate), '09:00'));
document.querySelectorAll('[data-close-agenda-request]').forEach(btn => btn.addEventListener('click', closeAgendaRequestModal));
agendaRequestModal?.addEventListener('click', (event) => { if (event.target === agendaRequestModal) closeAgendaRequestModal(); });
const monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
function dateKey(d){return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`}
function parseDate(date){const [y,m,d]=date.split('-').map(Number); return new Date(y,m-1,d)}
function eventDate(ev){return ev.date ? parseDate(ev.date) : null}
function updateTitles(){document.getElementById('date-label').textContent=`${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;document.getElementById('toolbar-title').textContent=currentView==='day'?'Vista diaria':currentView==='month'?'Vista mensual':'Vista semanal'}
function renderWeek(){const wrap=document.getElementById('week-view');wrap.innerHTML='';let start=new Date(currentDate);start.setDate(currentDate.getDate()-currentDate.getDay()+1);const grid=document.createElement('div');grid.className='week-grid';for(let i=0;i<7;i++){let d=new Date(start);d.setDate(start.getDate()+i);let col=document.createElement('div');col.className='week-column';col.innerHTML=`<div class="week-column-header"><span>${dayNames[d.getDay()]}</span><small>${d.getDate()} ${monthNames[d.getMonth()].slice(0,3)}</small><button class="calendar-add-mini" type="button" aria-label="Solicitar cita">+</button></div>`;col.querySelector('.calendar-add-mini')?.addEventListener('click',(event)=>{event.stopPropagation();openAgendaRequestModal(dateKey(d),'09:00')});let found=agendaEvents.filter(ev=>ev.date===dateKey(d));if(!found.length){let empty=document.createElement('button');empty.type='button';empty.className='empty-slot empty-slot-action';empty.textContent='Sin sesiones · solicitar';empty.addEventListener('click',()=>openAgendaRequestModal(dateKey(d),'09:00'));col.appendChild(empty)}found.forEach(ev=>col.appendChild(eventCard(ev)));grid.appendChild(col)}wrap.appendChild(grid)}
function renderDay(){const wrap=document.getElementById('day-view');let key=dateKey(currentDate);let found=agendaEvents.filter(ev=>ev.date===key);wrap.innerHTML=`<div class="day-header"><div class="day-date">${currentDate.getDate()} de ${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}</div><div class="day-sub">${found.length} sesiones registradas</div></div><div class="day-timeline"></div>`;let tl=wrap.querySelector('.day-timeline');for(let h=7;h<=21;h++){let hour=String(h).padStart(2,'0')+':00';let row=document.createElement('div');row.className='timeline-hour';row.innerHTML=`<span class="hour-label">${hour}</span><div class="timeline-events"></div>`;row.querySelector('.timeline-events').addEventListener('click',(event)=>{if(event.target.classList.contains('timeline-events')) openAgendaRequestModal(key,hour)});found.filter(ev=>(ev.time||'').slice(0,2)==String(h).padStart(2,'0')).forEach(ev=>row.querySelector('.timeline-events').appendChild(eventCard(ev)));tl.appendChild(row)}}
function renderMonth(){const wrap=document.getElementById('month-view');let first=new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);let startDay=first.getDay();let days=new Date(currentDate.getFullYear(), currentDate.getMonth()+1,0).getDate();wrap.innerHTML='<div class="month-header">'+dayNames.map(d=>`<span>${d}</span>`).join('')+'</div><div class="month-grid"></div>';let grid=wrap.querySelector('.month-grid');for(let i=0;i<startDay;i++){let c=document.createElement('div');c.className='month-cell empty';grid.appendChild(c)}for(let day=1;day<=days;day++){let d=new Date(currentDate.getFullYear(), currentDate.getMonth(), day);let c=document.createElement('div');c.className='month-cell';c.innerHTML=`<div class="month-day-number">${day}<button class="calendar-add-mini" type="button" aria-label="Solicitar cita">+</button></div>`;c.querySelector('.calendar-add-mini')?.addEventListener('click',(event)=>{event.stopPropagation();openAgendaRequestModal(dateKey(d),'09:00')});agendaEvents.filter(ev=>ev.date===dateKey(d)).slice(0,3).forEach(ev=>{let e=document.createElement('div');e.className='month-event';e.textContent=`${ev.time} ${ev.patient}`;e.onclick=()=>selectEvent(ev);c.appendChild(e)});grid.appendChild(c)}}
function eventCard(ev){let card=document.createElement('div');card.className='event-card';card.innerHTML=`<span class="event-time">${ev.time||''}</span><strong>${ev.title}</strong><span class="event-client">${ev.patient}</span>`;card.onclick=()=>selectEvent(ev);return card}
function selectEvent(ev){document.getElementById('detail-empty').hidden=true;document.getElementById('detail-content').hidden=false;document.getElementById('selected-title').textContent=ev.title;document.getElementById('selected-patient').textContent=ev.patient;document.getElementById('detail-date').textContent=ev.displayDate||'';document.getElementById('detail-modality').textContent=ev.modality||'';document.getElementById('detail-status').textContent=ev.status==='missed'?'Perdida / sin entrar':(ev.status||'');let link=document.getElementById('detail-session-link');if(ev.session_available&&ev.room_link){link.href=ev.room_link;link.textContent='Entrar a sesión';link.classList.remove('disabled');link.removeAttribute('aria-disabled')}else{link.href=`{{ route('profesional.sesion') }}?appointment_id=${ev.id}`;link.textContent=ev.session_ends_at?'Fuera de ventana de acceso':'Ver sesión';link.classList.add('disabled');link.setAttribute('aria-disabled','true')}}
function renderNext(){let box=document.getElementById('next-events-list'); if(!box) return; let now=new Date();let next=agendaEvents.filter(ev=>eventDate(ev) && eventDate(ev)>=new Date(now.getFullYear(),now.getMonth(),now.getDate())).slice(0,5);box.innerHTML=next.length?'':'<div class="backend-empty">Sin próximas sesiones.</div>';next.forEach(ev=>{let el=document.createElement('div');el.className='agenda-request-item';el.innerHTML=`<div><strong>${ev.patient}</strong><span>${ev.displayDate} · ${ev.modality}</span></div>`;box.appendChild(el)})}
function render(){document.querySelectorAll('.agenda-view-content>div').forEach(v=>v.classList.remove('active'));document.getElementById(`${currentView}-view`).classList.add('active');updateTitles();if(currentView==='day')renderDay();if(currentView==='week')renderWeek();if(currentView==='month')renderMonth();renderNext()}
document.querySelectorAll('[data-view]').forEach(btn=>btn.addEventListener('click',()=>{currentView=btn.dataset.view;document.querySelectorAll('[data-view]').forEach(b=>b.classList.remove('active'));btn.classList.add('active');render()}));
document.getElementById('prev-btn').addEventListener('click',()=>{currentDate.setDate(currentDate.getDate()+(currentView==='month'?-30:currentView==='week'?-7:-1));render()});
document.getElementById('next-btn').addEventListener('click',()=>{currentDate.setDate(currentDate.getDate()+(currentView==='month'?30:currentView==='week'?7:1));render()});
document.getElementById('today-btn-bottom').addEventListener('click',()=>{currentDate=new Date();render()});
document.querySelectorAll('[data-psych-request-filter]').forEach(btn=>btn.addEventListener('click',()=>{let f=btn.dataset.psychRequestFilter;document.querySelectorAll('[data-psych-request-filter]').forEach(b=>b.classList.remove('active'));btn.classList.add('active');document.querySelectorAll('[data-request-card]').forEach(card=>{card.style.display=(f==='all'||card.dataset.psychRequestType===f)?'grid':'none'})}));
render();
</script>
</body>
</html>
