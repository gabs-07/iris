<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="user-role" content="{{ auth()->user()->rol }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagar suscripción | IRIS</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/pago-cita.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body payment-page subscription-payment-page" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-psicologo.js') }}"></script>

<main class="dashboard-main">
  @include('partials.profesional-header', [
    'title' => 'Suscripción profesional',
    'subtitle' => 'Activa o renueva tu plan para usar agenda, pacientes, comunidad y herramientas clínicas.'
  ])

  @include('partials.frontend-alerts')

  <section class="payment-card subscription-card">
    <div class="payment-header">
      <span class="branding-badge">Pago de suscripción</span>
      <h1>{{ $plan['name'] }}</h1>
      <p class="card-subtitle">El cobro se autoriza en PayPal y el acceso se activa solo cuando PayPal confirma el pago.</p>
    </div>

    <div class="payment-summary-grid subscription-summary-grid">
      <div>
        <span>Profesional</span>
        <strong>{{ auth()->user()->nombre_completo }}</strong>
      </div>
      <div>
        <span>Correo</span>
        <strong>{{ auth()->user()->email }}</strong>
      </div>
      <div>
        <span>Plan</span>
        <strong>{{ $plan['cycle'] === 'annual' ? 'Anual' : 'Mensual' }}</strong>
      </div>
      <div>
        <span>Total</span>
        <strong>${{ number_format($plan['amount'], 2) }} MXN</strong>
      </div>
      <div>
        <span>Incluye</span>
        <strong>{{ $plan['features'] }}</strong>
      </div>
    </div>

    <div class="backend-actions subscription-actions">
      <a class="btn-outline" href="{{ route('profesional.pago-suscripcion', ['cycle' => 'monthly']) }}">Mensual $10</a>
      <a class="btn-outline" href="{{ route('profesional.pago-suscripcion', ['cycle' => 'annual']) }}">Anual $7,680</a>
      <button id="paypal-subscription-button" class="btn-primary" type="button">Pagar con PayPal</button>
    </div>
  </section>
</main>

<script>
const createUrl = @json(route('profesional.paypal.subscriptions.create'));
document.getElementById('paypal-subscription-button')?.addEventListener('click', async () => {
  const btn = document.getElementById('paypal-subscription-button');
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const body = new FormData();
  body.append('cycle', @json($plan['cycle']));

  btn.disabled = true;
  btn.textContent = 'Conectando con PayPal...';

  try {
    const res = await fetch(createUrl, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
      body
    });
    const data = await res.json();

    if (data.approval_url) {
      window.location.href = data.approval_url;
      return;
    }

    alert(data.message || 'No se pudo iniciar PayPal. Revisa credenciales .env.');
  } catch (error) {
    alert('No se pudo conectar con PayPal. Intenta de nuevo.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Pagar con PayPal';
  }
});
</script>
</body>
</html>
