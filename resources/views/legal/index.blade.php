<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentos legales | IRIS</title>
  <link rel="stylesheet" href="{{ asset('legal/css/legal.css') }}">
</head>
<body>
  <main class="legal">
    <header class="legal-header">
      <p class="brand">IRIS</p>
      <h1>Documentos legales</h1>
      <p class="meta">Índice de vistas legales convertidas a Blade.</p>
    </header>
    <section>
      <ul>
        <li><a href="{{ url('/legal/view/aviso_privacidad') }}">Aviso de privacidad integral</a></li>
        <li><a href="{{ url('/legal/view/terminos_condiciones') }}">Términos y condiciones</a></li>
        <li><a href="{{ url('/legal/view/consentimiento_informado') }}">Consentimiento informado</a></li>
        <li><a href="{{ url('/legal/view/consentimiento_datos_sensibles') }}">Consentimiento de datos sensibles</a></li>
        <li><a href="{{ url('/legal/view/politica_cancelaciones_reembolsos') }}">Política de cancelaciones y reembolsos</a></li>
        <li><a href="{{ url('/legal/view/politica_cookies') }}">Política de cookies</a></li>
        <li><a href="{{ url('/legal/view/reglas_comunidad') }}">Reglas de comunidad</a></li>
        <li><a href="{{ url('/legal/view/aviso_emergencias') }}">Aviso de emergencias</a></li>
        <li><a href="{{ url('/legal/view/condiciones_profesionales') }}">Condiciones profesionales</a></li>
        <li><a href="{{ url('/legal/view/consentimiento_comunicaciones') }}">Consentimiento de comunicaciones</a></li>
      </ul>
    </section>
  </main>
</body>
</html>
