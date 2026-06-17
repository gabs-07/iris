<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agendar cita | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auxilio.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/agendar-cita.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body schedule-appointment-page">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>
<main class="dashboard-main schedule-main">
  <header class="schedule-hero">
    <a class="schedule-back-link" href="{{ $isFirstBooking ? route('paciente.buscar-especialista') : route('paciente.gestion-citas') }}">← {{ $isFirstBooking ? 'Volver al directorio' : 'Volver a mis citas' }}</a>
    <div class="schedule-hero-grid">
      <section class="schedule-specialist-card">
        <div class="schedule-avatar"><img src="{{ asset('img/iris-logo-icon.png') }}" alt="IRIS"></div>
        <div class="schedule-specialist-copy">
          <h1>{{ $isFirstBooking ? 'Primera cita' : 'Cita de seguimiento' }}</h1>
          <p>
            @if($isFirstBooking)
              Esta solicitud se genera desde el directorio de especialistas. Después del pago, el profesional podrá aceptar, rechazar o proponer una nueva fecha.
            @else
              Esta sección es para continuar atención con profesionales con los que ya tienes seguimiento. Para una primera cita usa Buscar especialista.
            @endif
          </p>
          <div class="schedule-meta-list"><span>Pago seguro PayPal</span><span>Solicitud real</span><span>Datos clínicos protegidos</span></div>
        </div>
      </section>
      <aside class="schedule-price-card live-price-card">
        <span>Tarifa por sesión</span>
        <strong id="selected-price-label">Selecciona especialista</strong>
        <p id="selected-specialist-label">El monto se tomará del perfil aprobado del profesional.</p>
      </aside>
    </div>
  </header>
  @include('partials.frontend-alerts')
  @php
    $selectedSpecialist = collect($scheduleSpecialists)->firstWhere('id', $selectedSpecialistId) ?: collect($scheduleSpecialists)->first();
  @endphp

  <section class="schedule-layout">
    <div class="schedule-card schedule-form-card">
      <div class="schedule-progress wizard-progress" aria-label="Progreso de cita">
        <button class="step-pill active" data-go-step="1" type="button"><span>1</span> Motivo</button>
        <button class="step-pill" data-go-step="2" type="button"><span>2</span> Modalidad</button>
        <button class="step-pill" data-go-step="3" type="button"><span>3</span> Fecha</button>
        <button class="step-pill" data-go-step="4" type="button"><span>4</span> Confirmar</button>
      </div>
      <form method="POST" action="{{ route('paciente.agendar-cita.store') }}" class="schedule-form backend-form-grid" id="appointment-wizard-form">
        @csrf
        <input type="hidden" name="booking_origin" value="{{ $isFirstBooking ? 'directorio' : 'seguimiento' }}">

        <section class="schedule-step wizard-step active" data-step="1">
          <div class="step-heading">
            <span>Paso 1 de 4</span>
            <h2>Especialista y motivo</h2>
            <p>{{ $isFirstBooking ? 'Estás creando la primera solicitud con el especialista elegido en el directorio.' : 'Solo aparecen especialistas con los que ya tienes seguimiento.' }}</p>
          </div>
          @if(empty($scheduleSpecialists) || empty($selectedSpecialist))
            <div class="backend-empty followup-empty">
              Aún no tienes especialistas de seguimiento. Agenda tu primera cita desde el directorio.
              <a href="{{ route('paciente.buscar-especialista') }}" class="btn-primary btn-sm">Buscar especialista</a>
            </div>
          @else
            <input type="hidden" name="psychologist_id" id="specialist-id" value="{{ $selectedSpecialist['id'] }}">
            <section class="selected-specialist-static" id="selected-specialist-static"
              data-price="{{ $selectedSpecialist['price'] }}"
              data-name="{{ $selectedSpecialist['name'] }}"
              data-specialty="{{ $selectedSpecialist['specialty'] }}"
              data-availability='@json($selectedSpecialist["availability"] ?? [])'
              data-days='@json($selectedSpecialist["days"] ?? [])'
              data-duration="{{ $selectedSpecialist['duration'] ?? 50 }}">
              <span>Especialista</span>
              <h3>{{ $selectedSpecialist['name'] }}</h3>
              <p>{{ $selectedSpecialist['specialty'] }}</p>
              <strong>{{ $selectedSpecialist['price'] }}</strong>
            </section>
          @endif
          <div class="reason-grid">
            @foreach([
              'Primera sesión' => 'Quiero iniciar un proceso terapéutico.',
              'Seguimiento terapéutico' => 'Continuar mi proceso con este especialista.',
              'Ansiedad o estrés' => 'Necesito apoyo para manejar síntomas.',
              'Orientación inicial' => 'Necesito orientación para definir mi proceso.'
            ] as $reason => $description)
              <label class="choice-card"><input type="radio" name="reason" value="{{ $reason }}" @checked($loop->first)><span><strong>{{ $reason }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
          </div>
          <label class="schedule-field full-field">Notas para el especialista<textarea name="notes" rows="4" placeholder="Cuéntanos brevemente qué necesitas compartir antes de la sesión."></textarea></label>
          <div class="wizard-actions"><button class="btn-primary js-next-step" type="button">Siguiente</button></div>
        </section>

        <section class="schedule-step wizard-step" data-step="2" hidden>
          <div class="step-heading"><span>Paso 2 de 4</span><h2>Modalidad</h2><p>Selecciona cómo prefieres tomar la sesión.</p></div>
          <div class="mode-grid">
            <label class="mode-card"><input type="radio" name="modality" value="Videollamada" checked><span><strong>Videollamada</strong><small>Atención remota.</small></span></label>
            <label class="mode-card"><input type="radio" name="modality" value="Llamada"><span><strong>Llamada</strong><small>Comunicación telefónica.</small></span></label>
            <label class="mode-card"><input type="radio" name="modality" value="Presencial"><span><strong>Presencial</strong><small>Según disponibilidad del profesional.</small></span></label>
          </div>
          <div class="wizard-actions"><button class="btn-outline js-prev-step" type="button">Anterior</button><button class="btn-primary js-next-step" type="button">Siguiente</button></div>
        </section>

        <section class="schedule-step wizard-step" data-step="3" hidden>
          <div class="step-heading"><span>Paso 3 de 4</span><h2>Fecha y hora</h2><p>El profesional debe aceptar la solicitud para confirmar la cita.</p></div>
          <div class="backend-form-grid two">
            <label class="backend-field">Fecha<input type="date" name="appointment_date" min="{{ now()->toDateString() }}" required></label>
            <label class="backend-field">Hora<input type="time" name="appointment_time" required><small id="availability-help" class="muted-copy">Selecciona fecha para ver el horario permitido.</small></label>
          </div>
          <div class="wizard-actions"><button class="btn-outline js-prev-step" type="button">Anterior</button><button class="btn-primary js-next-step" type="button">Siguiente</button></div>
        </section>

        <section class="schedule-step wizard-step" data-step="4" hidden>
          <div class="step-heading"><span>Paso 4 de 4</span><h2>Confirmar</h2><p>Revisa la información. Al continuar se crea la solicitud y se abre el pago con PayPal.</p></div>
          <div class="appointment-confirm-card">
            <div><span>Especialista</span><strong id="confirm-specialist">Selecciona especialista</strong></div>
            <div><span>Tarifa</span><strong id="confirm-price">Pendiente</strong></div>
            <div><span>Motivo</span><strong id="confirm-reason">Pendiente</strong></div>
            <div><span>Modalidad</span><strong id="confirm-modality">Pendiente</strong></div>
            <div><span>Fecha y hora</span><strong id="confirm-date">Pendiente</strong></div>
          </div>
          <div class="legal-appointment-checks" style="margin:18px 0; padding:16px; border-radius:18px; background:#f5fbf8; border:1px solid #d9eee5; display:grid; gap:10px; color:#25483b;">
            <label style="display:flex; gap:10px; align-items:flex-start;"><input type="checkbox" name="acepta_consentimiento_informado" required style="margin-top:4px;"> <span>Declaro que leí y acepto el <a href="{{ url('/legal/view/consentimiento_informado') }}" target="_blank">Consentimiento Informado</a> aplicable a la atención psicológica/psiquiátrica.</span></label>
            <label style="display:flex; gap:10px; align-items:flex-start;"><input type="checkbox" name="acepta_cancelaciones" required style="margin-top:4px;"> <span>Acepto la <a href="{{ url('/legal/view/politica_cancelaciones_reembolsos') }}" target="_blank">Política de Cancelaciones y Reembolsos</a> y reconozco que IRIS no sustituye servicios de emergencia.</span></label>
            <small>Para situaciones de riesgo inmediato consulta el <a href="{{ url('/legal/view/aviso_emergencias') }}" target="_blank">Aviso de Emergencias</a>.</small>
          </div>
          <div class="wizard-actions"><button class="btn-outline js-prev-step" type="button">Anterior</button><button class="btn-primary" type="submit" @if(empty($scheduleSpecialists)) disabled @endif>Continuar pago</button></div>
        </section>
      </form>
    </div>
    <aside class="schedule-card schedule-summary-card">
      <h2>{{ $isFirstBooking ? 'Flujo de primera cita' : 'Flujo de seguimiento' }}</h2>
      <dl class="summary-list"><div><dt>1. Solicitud</dt><dd>Capturas motivo, modalidad y horario.</dd></div><div><dt>2. Pago</dt><dd>PayPal confirma la transacción.</dd></div><div><dt>3. Profesional</dt><dd>Acepta, rechaza o propone reagenda.</dd></div></dl>
      <p class="summary-note">No se registra pago si PayPal no confirma la transacción.</p>
    </aside>
  </section>
</main>
@include('partials.floating-auxilio')
<script>
(() => {
  const form = document.getElementById('appointment-wizard-form');
  const specialistBox = document.getElementById('selected-specialist-static');
  const price = document.getElementById('selected-price-label');
  const label = document.getElementById('selected-specialist-label');
  const steps = Array.from(document.querySelectorAll('.wizard-step'));
  const pills = Array.from(document.querySelectorAll('.step-pill'));
  let current = 1;

  function selectedOption() {
    return specialistBox ? {
      value: document.getElementById('specialist-id')?.value || '',
      dataset: specialistBox.dataset
    } : null;
  }
  function updatePrice() {
    const option = selectedOption();
    if (!option || !option.value) {
      if (price) price.textContent = 'Especialista no seleccionado';
      if (label) label.textContent = 'El monto se tomará del perfil aprobado del profesional.';
      return;
    }
    if (price) price.textContent = option.dataset.price || 'Tarifa registrada';
    if (label) label.textContent = `${option.dataset.name || 'Especialista'} · ${option.dataset.specialty || 'Especialidad registrada'}`;
  }
  function updateConfirm() {
    const option = selectedOption();
    document.getElementById('confirm-specialist').textContent = option?.value ? `${option.dataset.name} · ${option.dataset.specialty}` : 'Especialista no seleccionado';
    document.getElementById('confirm-price').textContent = option?.dataset.price || 'Pendiente';
    document.getElementById('confirm-reason').textContent = form.querySelector('[name="reason"]:checked')?.value || 'Pendiente';
    document.getElementById('confirm-modality').textContent = form.querySelector('[name="modality"]:checked')?.value || 'Pendiente';
    const date = form.querySelector('[name="appointment_date"]')?.value || '';
    const time = form.querySelector('[name="appointment_time"]')?.value || '';
    document.getElementById('confirm-date').textContent = date && time ? `${date} ${time}` : 'Pendiente';
  }
  function showStep(step) {
    current = Math.max(1, Math.min(4, step));
    steps.forEach(s => { const active = Number(s.dataset.step) === current; s.hidden = !active; s.classList.toggle('active', active); });
    pills.forEach(p => p.classList.toggle('active', Number(p.dataset.goStep) <= current));
    updatePrice(); updateConfirm();
  }
  function validateCurrent() {
    const active = steps.find(s => Number(s.dataset.step) === current);
    const fields = Array.from(active?.querySelectorAll('input,select,textarea') || []).filter(el => !el.disabled && !['hidden','radio'].includes(el.type));
    for (const field of fields) { if (!field.checkValidity()) { field.reportValidity(); return false; } }
    if (current === 1 && !form.querySelector('[name="reason"]:checked')) return false;
    if (current === 2 && !form.querySelector('[name="modality"]:checked')) return false;
    return true;
  }
  document.querySelectorAll('.js-next-step').forEach(btn => btn.addEventListener('click', () => { if (validateCurrent()) showStep(current + 1); }));
  document.querySelectorAll('.js-prev-step').forEach(btn => btn.addEventListener('click', () => showStep(current - 1)));
  pills.forEach(p => p.addEventListener('click', () => { const target = Number(p.dataset.goStep); if (target <= current || validateCurrent()) showStep(target); }));
  const dayNames = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
  const dateInput = form?.querySelector('[name="appointment_date"]');
  const timeInput = form?.querySelector('[name="appointment_time"]');
  const help = document.getElementById('availability-help');
  function normalizeRange(raw){
    if (!raw) return null;
    if (Array.isArray(raw) && raw.length >= 2) return {inicio: raw[0], fin: raw[1]};
    if (typeof raw === 'object' && raw.inicio && raw.fin) return {inicio: raw.inicio, fin: raw.fin};
    return null;
  }
  function selectedAvailability(){
    try { return JSON.parse(specialistBox?.dataset.availability || '{}'); } catch { return {}; }
  }
  function selectedDays(){
    try { return JSON.parse(specialistBox?.dataset.days || '[]'); } catch { return []; }
  }
  function updateAvailabilityHint(){
    if (!dateInput || !timeInput) return;
    const value = dateInput.value;
    if (!value) { if (help) help.textContent = 'Selecciona fecha para ver el horario permitido.'; return; }
    const date = new Date(value + 'T12:00:00');
    const day = dayNames[date.getDay()];
    const days = selectedDays();
    const availability = selectedAvailability();
    const range = normalizeRange(availability[day]);
    timeInput.removeAttribute('min');
    timeInput.removeAttribute('max');
    if (days.length && !days.includes(day)) {
      if (help) help.textContent = `El profesional no atiende en ${day}. Elige otro día.`;
      return;
    }
    if (range) {
      timeInput.min = range.inicio;
      timeInput.max = range.fin;
      if (help) help.textContent = `Horario permitido para ${day}: ${range.inicio} a ${range.fin}.`;
    } else if (help) {
      help.textContent = `Día permitido: ${day}. El sistema validará disponibilidad al guardar.`;
    }
  }
  dateInput?.addEventListener('change', updateAvailabilityHint);
  timeInput?.addEventListener('change', updateAvailabilityHint);
  form?.addEventListener('input', updateConfirm);
  updatePrice(); updateAvailabilityHint(); showStep(1);
})();
</script>
</body>
</html>
