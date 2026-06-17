<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Completar perfil | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <style>
        body { background: #f5fbf8; }
        .complete-shell { max-width: 1100px; margin: 32px auto; padding: 0 18px; }
        .complete-card { background: #fff; border: 1px solid #dfeee7; border-radius: 22px; padding: 26px; box-shadow: 0 14px 35px rgba(27, 83, 63, .08); }
        .complete-header { margin-bottom: 20px; }
        .complete-header h1 { margin: 0 0 8px; color: #1e5a45; }
        .complete-header p { margin: 0; color: #667; }
        .section-title { margin: 28px 0 12px; color: #1e5a45; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field { display: flex; flex-direction: column; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-weight: 700; color: #25483b; }
        .field input, .field select, .field textarea { border: 1px solid #cfe2d9; border-radius: 12px; padding: 12px 14px; font: inherit; background: #fff; }
        .field textarea { min-height: 90px; resize: vertical; }
        .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; }
        .alert-success { background: #e0f2e9; color: #1e5a45; }
        .alert-warning { background: #fff7da; color: #775b00; }
        .alert-error { background: #ffe4e4; color: #842029; }
        .actions { display: flex; gap: 12px; align-items: center; margin-top: 26px; }
        .btn-primary { border: none; border-radius: 14px; padding: 12px 20px; background: #2c7a5e; color: #fff; font-weight: 800; cursor: pointer; }
        .btn-secondary { color: #1e5a45; text-decoration: none; font-weight: 800; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } .complete-card { padding: 18px; } }
    </style>
</head>
<body>
    <main class="complete-shell">
        <section class="complete-card">
            <div class="complete-header">
                <h1>Completa tu perfil de paciente</h1>
                <p>Estos datos no se piden en el registro. Sirven para personalizar la atención y activar tu cuenta dentro de IRIS.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Revisa los campos:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('perfil.completar.store') }}" method="POST">
                @csrf

                @php
                    $generos = [''=>'Seleccionar…','femenino'=>'Femenino','masculino'=>'Masculino','no-binario'=>'No binario','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
                    $relacionesEmergencia = ['madre'=>'Madre','padre'=>'Padre','hermano/a'=>'Hermano/a','pareja'=>'Pareja','amigo/a'=>'Amigo/a','familiar'=>'Familiar','tutor/a'=>'Tutor/a','otro'=>'Otro'];
                    $estadosCiviles = [''=>'Seleccionar…','soltero/a'=>'Soltero/a','casado/a'=>'Casado/a','union-libre'=>'Unión libre','separado/a'=>'Separado/a','divorciado/a'=>'Divorciado/a','viudo/a'=>'Viudo/a','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
                @endphp

                <h2 class="section-title">Datos personales</h2>
                <div class="grid">
                    <div class="field">
                        <label>Nombre(s) *</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" placeholder="Ej. Israel" autocomplete="given-name" required>
                    </div>
                    <div class="field">
                        <label>Apellidos</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" placeholder="Ej. Márquez Cárdenas" autocomplete="family-name">
                    </div>
                    <div class="field">
                        <label>Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $user->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($user->fecha_nacimiento)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="field">
                        <label>Género</label>
                        <select name="genero">
                            @foreach($generos as $value => $label)
                                <option value="{{ $value }}" @selected(old('genero', $user->genero) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" value="{{ old('telefono', $user->telefono) }}" placeholder="55 1234 5678" autocomplete="tel" inputmode="tel" required>
                    </div>
                    <div class="field">
                        <label>Ocupación</label>
                        <input type="text" name="ocupacion" value="{{ old('ocupacion', $user->patientProfile?->ocupacion) }}" placeholder="Ej. Estudiante, ingeniero/a, docente">
                    </div>
                </div>

                <h2 class="section-title">Contacto de emergencia</h2>
                <div class="grid">
                    <div class="field">
                        <label>Nombre del contacto *</label>
                        <input type="text" name="emergencia_nombre" value="{{ old('emergencia_nombre', $user->emergencyContact?->nombre) }}" placeholder="Ej. María Cárdenas" required>
                    </div>
                    <div class="field">
                        <label>Relación *</label>
                        <select name="emergencia_relacion" required>
                            <option value="">Seleccionar…</option>
                            @foreach($relacionesEmergencia as $value => $label)
                                <option value="{{ $value }}" @selected(old('emergencia_relacion', $user->emergencyContact?->relacion) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Teléfono de emergencia *</label>
                        <input type="tel" name="emergencia_telefono" value="{{ old('emergencia_telefono', $user->emergencyContact?->telefono) }}" placeholder="55 1234 5678" inputmode="tel" required>
                    </div>
                </div>

                <h2 class="section-title">Información clínica inicial</h2>
                <div class="grid">
                    <div class="field">
                        <label>Terapia previa</label>
                        <select name="terapia_previa">
                            @foreach(['' => 'Seleccionar…', 'no' => 'No', 'si' => 'Sí', 'actualmente' => 'Actualmente en terapia'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('terapia_previa', $user->patientProfile?->terapia_previa) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Medicación actual</label>
                        <select name="medicacion_actual">
                            @foreach(['' => 'Seleccionar…', 'no' => 'No', 'si' => 'Sí', 'prefiero-comentarlo' => 'Prefiero comentarlo en sesión'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('medicacion_actual', $user->patientProfile?->medicacion_actual) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Estado civil</label>
                        <select name="estado_civil">
                            @foreach($estadosCiviles as $value => $label)
                                <option value="{{ $value }}" @selected(old('estado_civil', $user->patientProfile?->estado_civil) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full">
                        <label>Motivo principal de consulta</label>
                        <textarea name="motivo_consulta" placeholder="Ej. Me gustaría trabajar ansiedad, estrés o cambios recientes en mi vida.">{{ old('motivo_consulta', $user->patientProfile?->motivo_consulta) }}</textarea>
                    </div>
                    <div class="field full">
                        <label>Objetivos terapéuticos</label>
                        <textarea name="objetivos" placeholder="Ej. Aprender herramientas para regular emociones y mejorar mi descanso.">{{ old('objetivos', $user->patientProfile?->objetivos) }}</textarea>
                    </div>
                    <div class="field full">
                        <label>Antecedentes importantes</label>
                        <textarea name="antecedentes" placeholder="Ej. Terapias previas, diagnósticos anteriores o eventos relevantes.">{{ old('antecedentes', $user->patientProfile?->antecedentes) }}</textarea>
                    </div>
                    <div class="field full">
                        <label>Alergias o restricciones médicas</label>
                        <textarea name="alergias" placeholder="Ej. Alergias conocidas, restricciones médicas o medicamentos que no toleras.">{{ old('alergias', $user->patientProfile?->alergias) }}</textarea>
                    </div>
                    <div class="field full">
                        <label>Domicilio o referencia general</label>
                        <textarea name="domicilio" placeholder="Ej. Alcaldía o municipio, ciudad y estado.">{{ old('domicilio', $user->patientProfile?->domicilio) }}</textarea>
                    </div>
                </div>

                <h2 class="section-title">Seguridad</h2>
                <div class="grid">
                    <div class="field">
                        <label>Nueva contraseña</label>
                        <input type="password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" autocomplete="new-password">
                    </div>
                </div>

                <div class="legal-profile-note" style="margin-top:24px; padding:14px 16px; border-radius:16px; background:#f5fbf8; border:1px solid #d9eee5; color:#25483b; font-size:.95rem; line-height:1.55;">
                    Antes de guardar, puedes consultar el <a href="{{ url('/legal/view/aviso_privacidad') }}" target="_blank">Aviso de Privacidad</a>,
                    el <a href="{{ url('/legal/view/consentimiento_datos_sensibles') }}" target="_blank">Consentimiento de Datos Sensibles</a>
                    y el <a href="{{ url('/legal') }}" target="_blank">Centro Legal de IRIS</a>.
                </div>

                <div class="actions">
                    <button class="btn-primary" type="submit">Guardar y continuar</button>
                    <a class="btn-secondary" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</a>
                </div>
            </form>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </section>
    </main>
</body>
</html>
