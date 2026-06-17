<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Completar perfil profesional | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
    <style>
        body { background: #f5fbf8; }
        .complete-shell { max-width: 1180px; margin: 32px auto; padding: 0 18px; }
        .complete-card { background: #fff; border: 1px solid #dfeee7; border-radius: 22px; padding: 26px; box-shadow: 0 14px 35px rgba(27, 83, 63, .08); }
        .complete-header { margin-bottom: 20px; }
        .complete-header h1 { margin: 0 0 8px; color: #1e5a45; }
        .complete-header p { margin: 0; color: #667; }
        .status-card { border-radius: 16px; padding: 14px 16px; margin: 14px 0 22px; background: #e0f2e9; color: #1e5a45; border: 1px solid #bee6d4; }
        .status-card.pending { background: #fff7da; color: #775b00; border-color: #f3dfa2; }
        .status-card.rejected { background: #ffe4e4; color: #842029; border-color: #ffc7c7; }
        .section-title { margin: 28px 0 12px; color: #1e5a45; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field { display: flex; flex-direction: column; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-weight: 700; color: #25483b; }
        .field input, .field select, .field textarea { border: 1px solid #cfe2d9; border-radius: 12px; padding: 12px 14px; font: inherit; background: #fff; }
        .field textarea { min-height: 90px; resize: vertical; }
        .checkbox-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .checkbox-row label { border: 1px solid #cfe2d9; border-radius: 999px; padding: 8px 12px; font-weight: 600; background: #fff; }
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
    @php
        $profile = $user->professionalProfile;
        $status = $user->professional_status;
        $statusClass = $status === 'pending' ? 'pending' : ($status === 'rejected' ? 'rejected' : '');
        $enfoques = old('enfoques', $profile?->enfoques ?? []);
        $poblaciones = old('poblaciones', $profile?->poblaciones ?? []);
        $areas = old('areas', $profile?->areas ?? []);
        $diasAtencion = old('dias_atencion', $profile?->dias_atencion ?? []);
        $formacionAcademica = old('formacion_academica_text', implode("\n", (array) ($profile?->formacion_academica ?? [])));
        $especialidadesTexto = old('especialidades_text', implode(', ', (array) ($profile?->especialidades ?? [])));
        $documentos = $profile?->documentos ?? [];
        $generos = [''=>'Seleccionar…','femenino'=>'Femenino','masculino'=>'Masculino','no-binario'=>'No binario','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
        $relacionesEmergencia = ['madre'=>'Madre','padre'=>'Padre','hermano/a'=>'Hermano/a','pareja'=>'Pareja','amigo/a'=>'Amigo/a','familiar'=>'Familiar','tutor/a'=>'Tutor/a','otro'=>'Otro'];
    @endphp

    <main class="complete-shell">
        <section class="complete-card">
            <div class="complete-header">
                <h1>Completa tu perfil profesional</h1>
                <p>El registro solo crea la cuenta. Aquí se capturan los datos que revisará el administrador antes de autorizar tu perfil público.</p>
            </div>

            @if ($status === 'pending')
                <div class="status-card pending"><strong>Perfil en revisión.</strong> Puedes actualizarlo si necesitas corregir datos; al guardar se reenviará a autorización.</div>
            @elseif ($status === 'approved')
                <div class="status-card"><strong>Perfil aprobado.</strong> Puedes actualizar tus datos; los cambios volverán a revisión administrativa.</div>
            @elseif ($status === 'rejected')
                <div class="status-card rejected"><strong>Perfil rechazado.</strong> Motivo: {{ $user->professional_rejection_reason ?: 'Sin observaciones registradas.' }} Corrige la información y vuelve a enviarla.</div>
            @else
                <div class="status-card pending"><strong>Perfil incompleto.</strong> Completa la información para enviarla a autorización.</div>
            @endif

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

            <form action="{{ route('perfil.completar.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <h2 class="section-title">Datos personales</h2>
                <div class="grid">
                    <div class="field"><label>Nombre(s) *</label><input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" placeholder="Ej. Sofía" autocomplete="given-name" required></div>
                    <div class="field"><label>Apellidos</label><input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" placeholder="Ej. Hernández López" autocomplete="family-name"></div>
                    <div class="field"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $user->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($user->fecha_nacimiento)->format('Y-m-d') : '') }}"></div>
                    <div class="field"><label>Género</label>
                        <select name="genero">
                            @foreach($generos as $value => $label)
                                <option value="{{ $value }}" @selected(old('genero', $user->genero) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Teléfono *</label><input type="tel" name="telefono" value="{{ old('telefono', $user->telefono) }}" placeholder="55 1234 5678" autocomplete="tel" inputmode="tel" required></div>
                    <div class="field"><label>Tipo profesional</label><input type="text" value="{{ ucfirst($user->rol) }}" disabled></div>
                </div>

                <h2 class="section-title">Contacto de emergencia</h2>
                <div class="grid">
                    <div class="field"><label>Nombre del contacto *</label><input type="text" name="emergencia_nombre" value="{{ old('emergencia_nombre', $user->emergencyContact?->nombre) }}" placeholder="Ej. Laura Hernández" required></div>
                    <div class="field"><label>Relación *</label><select name="emergencia_relacion" required><option value="">Seleccionar…</option>@foreach($relacionesEmergencia as $value => $label)<option value="{{ $value }}" @selected(old('emergencia_relacion', $user->emergencyContact?->relacion) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label>Teléfono de emergencia *</label><input type="tel" name="emergencia_telefono" value="{{ old('emergencia_telefono', $user->emergencyContact?->telefono) }}" placeholder="55 1234 5678" inputmode="tel" required></div>
                </div>

                <h2 class="section-title">Credenciales y formación</h2>
                <div class="grid">
                    <div class="field"><label>Título profesional *</label><input type="text" name="titulo_profesional" value="{{ old('titulo_profesional', $profile?->titulo_profesional) }}" placeholder="Ej. Licenciatura en Psicología" required></div>
                    <div class="field"><label>Especialidad principal *</label><input type="text" name="especialidad_principal" value="{{ old('especialidad_principal', $profile?->especialidad_principal) }}" placeholder="Ej. Psicología clínica y manejo emocional" required></div>
                    <div class="field"><label>Cédula profesional *</label><input type="text" name="cedula_profesional" value="{{ old('cedula_profesional', $profile?->cedula_profesional) }}" placeholder="Ej. 12345678" inputmode="numeric" required></div>
                    <div class="field"><label>Cédula de especialidad</label><input type="text" name="cedula_especialidad" value="{{ old('cedula_especialidad', $profile?->cedula_especialidad) }}" placeholder="Ej. 87654321" inputmode="numeric"></div>
                    <div class="field"><label>Institución</label><input type="text" name="institucion" value="{{ old('institucion', $profile?->institucion) }}" placeholder="Ej. Universidad Nacional Autónoma de México"></div>
                    <div class="field"><label>Posgrado</label><input type="text" name="posgrado" value="{{ old('posgrado', $profile?->posgrado) }}" placeholder="Ej. Maestría en terapia cognitivo-conductual"></div>
                    <div class="field full"><label>Formación académica pública</label><textarea name="formacion_academica_text" placeholder="Una línea por grado, certificación o institución.">{{ $formacionAcademica }}</textarea></div>
                    <div class="field"><label>Años de experiencia</label><input type="number" min="0" max="80" name="experiencia_anios" value="{{ old('experiencia_anios', $profile?->experiencia_anios) }}" placeholder="Ej. 6"></div>
                    <div class="field"><label>Asociaciones profesionales</label><input type="text" name="asociaciones" value="{{ old('asociaciones', $profile?->asociaciones) }}" placeholder="Ej. Colegio Mexicano de Psicología"></div>
                    <div class="field full"><label>Documentos de respaldo PDF/imagen</label><input type="file" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @if(count($documentos))<small>Documentos cargados: {{ count($documentos) }}</small>@endif
                    </div>
                </div>

                <h2 class="section-title">Especialidades clínicas</h2>
                <div class="grid">
                    <div class="field full">
                        <label>Enfoques</label>
                        <div class="checkbox-row">
                            @foreach(['TCC', 'Humanista', 'Psicoanálisis', 'Sistémica', 'Mindfulness', 'Neuropsicología'] as $value)
                                <label><input type="checkbox" name="enfoques[]" value="{{ $value }}" @checked(in_array($value, (array) $enfoques, true))> {{ $value }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field full">
                        <label>Poblaciones</label>
                        <div class="checkbox-row">
                            @foreach(['Niños', 'Adolescentes', 'Adultos', 'Parejas', 'Familias', 'Adultos mayores'] as $value)
                                <label><input type="checkbox" name="poblaciones[]" value="{{ $value }}" @checked(in_array($value, (array) $poblaciones, true))> {{ $value }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field full"><label>Especialidades que verá el paciente</label><input name="especialidades_text" value="{{ $especialidadesTexto }}" placeholder="Ansiedad, Estrés laboral, Depresión, Ataques de pánico"></div>
                    <div class="field full">
                        <label>Áreas de atención</label>
                        <div class="checkbox-row">
                            @foreach(['Ansiedad', 'Depresión', 'Duelo', 'Estrés', 'Relaciones', 'Adicciones', 'Trauma', 'TDAH'] as $value)
                                <label><input type="checkbox" name="areas[]" value="{{ $value }}" @checked(in_array($value, (array) $areas, true))> {{ $value }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <h2 class="section-title">Servicios, tarifas y disponibilidad</h2>
                <div class="grid">
                    <div class="field"><label>Modalidad</label>
                        <select name="modalidad">
                            @foreach(['ambas' => 'Presencial y en línea', 'online' => 'En línea', 'presencial' => 'Presencial'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('modalidad', $profile?->modalidad ?? 'ambas') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Ubicación</label><input type="text" name="ubicacion" value="{{ old('ubicacion', $profile?->ubicacion) }}" placeholder="Ej. Ciudad de México o atención en línea"></div>
                    <div class="field"><label>Idiomas</label><input type="text" name="idiomas" value="{{ old('idiomas', $profile?->idiomas) }}" placeholder="Ej. Español, inglés"></div>
                    <div class="field"><label>Duración por sesión</label><input type="number" min="30" max="180" step="5" name="duracion_sesion" value="{{ old('duracion_sesion', $profile?->duracion_sesion ?? 50) }}" placeholder="Ej. 50"></div>
                    <div class="field"><label>Costo mínimo</label><input type="number" min="0" step="50" name="costo_min" value="{{ old('costo_min', $profile?->costo_min) }}" placeholder="Ej. 800"></div>
                    <div class="field"><label>Costo máximo</label><input type="number" min="0" step="50" name="costo_max" value="{{ old('costo_max', $profile?->costo_max) }}" placeholder="Ej. 1200"></div>
                    <div class="field full"><label>Biografía pública</label><textarea name="biografia" placeholder="Describe tu enfoque, experiencia y estilo de acompañamiento.">{{ old('biografia', $profile?->biografia) }}</textarea></div>
                    <div class="field full"><label>Servicios</label><textarea name="servicios" placeholder="Ej. Terapia individual, orientación inicial, seguimiento terapéutico.">{{ old('servicios', $profile?->servicios) }}</textarea></div>
                    <div class="field full"><label>Presentación profesional</label><textarea name="presentacion" placeholder="Ej. Mensaje breve para explicar cómo trabajas con tus pacientes.">{{ old('presentacion', $profile?->presentacion) }}</textarea></div>
                    <div class="field full"><label>Días de atención visibles</label><div class="checkbox-row">@foreach(['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo'] as $value => $label)<label><input type="checkbox" name="dias_atencion[]" value="{{ $value }}" @checked(in_array($value, (array) $diasAtencion, true))> {{ $label }}</label>@endforeach</div></div>
                    <div class="field"><label>Próximo espacio visible</label><input type="text" name="proximo_espacio" value="{{ old('proximo_espacio', $profile?->proximo_espacio) }}" placeholder="Ej. Mañana, 16:00 hrs"></div>
                    <div class="field full"><label>Horario de servicio por día</label><p class="form-hint">Ejemplo: si atiendes de 8:00 AM a 3:00 PM, captura 08:00 y 15:00. Al agendar, IRIS solo permitirá citas dentro de ese rango.</p><div class="availability-grid">@foreach(['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo'] as $value => $label)<div class="availability-row"><strong>{{ $label }}</strong><input type="time" name="disponibilidad[{{ $value }}][inicio]" value="{{ old('disponibilidad.'.$value.'.inicio', data_get($profile?->disponibilidad, $value.'.inicio', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.0') : null)) }}"><span>a</span><input type="time" name="disponibilidad[{{ $value }}][fin]" value="{{ old('disponibilidad.'.$value.'.fin', data_get($profile?->disponibilidad, $value.'.fin', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.1') : null)) }}"></div>@endforeach</div></div>
                </div>

                <h2 class="section-title">Seguridad</h2>
                <div class="grid">
                    <div class="field"><label>Nueva contraseña</label><input type="password" name="password" autocomplete="new-password"></div>
                    <div class="field"><label>Confirmar nueva contraseña</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>
                </div>

                <div class="legal-profile-note" style="margin-top:24px; padding:14px 16px; border-radius:16px; background:#f5fbf8; border:1px solid #d9eee5; color:#25483b; font-size:.95rem; line-height:1.55;">
                    Antes de guardar, puedes consultar el <a href="{{ url('/legal/view/aviso_privacidad') }}" target="_blank">Aviso de Privacidad</a>,
                    el <a href="{{ url('/legal/view/consentimiento_datos_sensibles') }}" target="_blank">Consentimiento de Datos Sensibles</a>
                    y el <a href="{{ url('/legal') }}" target="_blank">Centro Legal de IRIS</a>.
                </div>

                <div class="actions">
                    <button class="btn-primary" type="submit">Enviar perfil a autorización</button>
                    <a class="btn-secondary" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</a>
                </div>
            </form>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </section>
    </main>
</body>
</html>
