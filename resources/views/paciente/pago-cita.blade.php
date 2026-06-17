<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagar cita | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/pago-cita.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body payment-page">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-paciente.js') }}"></script>
<main class="dashboard-main payment-main-fixed">
  <section class="payment-container">
    <a class="payment-back-link" href="{{ route('paciente.gestion-citas') }}">← Volver a mis citas</a>
    <header class="payment-header">
      <span class="section-kicker">Pago seguro</span>
      <h1>Confirmar pago de cita</h1>
      <p class="card-subtitle">La solicitud se enviará al profesional solo después de que PayPal confirme el pago.</p>
    </header>
    @include('partials.frontend-alerts')

    @if($appointmentModel)
      <div class="payment-grid backend-payment-grid">
        <article class="payment-summary-card">
          <h2>Resumen de la cita</h2>
          <div class="summary-specialist">
            <div class="summary-avatar">{{ \Illuminate\Support\Str::of($appointment['specialist'])->explode(' ')->map(fn($w)=>\Illuminate\Support\Str::substr($w,0,1))->take(2)->join('') }}</div>
            <div><strong>{{ $appointment['specialist'] }}</strong><p>{{ $appointment['modality'] }}</p></div>
          </div>
          <dl class="summary-details backend-payment-details">
            <div><dt>Motivo</dt><dd>{{ $appointment['reason'] }}</dd></div>
            <div><dt>Fecha</dt><dd>{{ $appointment['date'] }} {{ $appointment['time'] }}</dd></div>
            <div><dt>Modalidad</dt><dd>{{ $appointment['modality'] }}</dd></div>
            <div class="total-row"><dt>Total</dt><dd>${{ number_format($appointment['amount'],2) }} MXN</dd></div>
          </dl>
          <p class="payment-note">No cierres esta ventana hasta volver desde PayPal. IRIS solo registra el pago cuando PayPal confirma la captura.</p>
        </article>

        <article class="payment-methods-card">
          <div class="payment-tabs"><button class="pay-tab active" type="button">PayPal</button></div>
          <div class="pay-panel">
            <div class="paypal-info">
              <img src="{{ asset('img/paypal.svg') }}" alt="PayPal" class="paypal-logo" width="140">
              <h2>Completa tu pago con PayPal</h2>
              <p class="card-subtitle">Se abrirá la pantalla segura de PayPal para autorizar el cobro.</p>
              <button id="paypal-pay-button" class="btn-primary btn-full" type="button">Pagar con PayPal</button>
              <a class="btn-outline btn-full mt-1" href="{{ route('paciente.gestion-citas') }}">Pagar después</a>
              <p class="disclaimer">Tu solicitud quedará como pendiente de pago hasta que PayPal confirme la transacción.</p>
            </div>
          </div>
        </article>
      </div>
    @else
      <div class="payment-summary-card backend-empty-state-card">
        <div class="backend-empty">No hay una cita pendiente de pago.</div>
        <a class="btn-primary" href="{{ route('paciente.agendar-cita') }}">Agendar cita</a>
      </div>
    @endif
  </section>
</main>
<script>
@if($appointmentModel)
const createUrl = @json(route('paciente.paypal.appointments.create', $appointmentModel));
document.getElementById('paypal-pay-button')?.addEventListener('click', async()=>{
  const btn = document.getElementById('paypal-pay-button');
  const token=document.querySelector('meta[name="csrf-token"]').content;
  btn.disabled = true;
  btn.textContent = 'Conectando con PayPal...';
  try {
    const res=await fetch(createUrl,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'}});
    const data=await res.json();
    if(data.approval_url){window.location.href=data.approval_url;return;}
    alert(data.message||'No se pudo iniciar PayPal. Revisa credenciales .env.');
  } catch (error) {
    alert('No se pudo conectar con PayPal. Intenta de nuevo.');
  }
  btn.disabled = false;
  btn.textContent = 'Pagar con PayPal';
});
@endif
</script>
</body>
</html>
