<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-role" content="{{ auth()->user()->rol }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis pacientes | IRIS</title>
    <link rel="stylesheet" href="{{ asset('css/fontawesome-local.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pacientes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iris-backend.css') }}">
</head>
<body class="dashboard-body" data-role="{{ auth()->user()->rol }}">
<div id="menu-dinamico"></div>
<script src="{{ asset('js/menu-psicologo.js') }}"></script>

<main class="dashboard-main patients-page">
    <header class="dashboard-header agenda-header">
        <div>
            <h1>Mis pacientes</h1>
            <p>Gestiona expedientes clínicos, tareas terapéuticas y notas profesionales.</p>
        </div>
        <div class="patients-header-actions">
            @include('partials.notification-modal')
            <button class="btn-primary" id="open-new-patient-modal" type="button"><i class="fas fa-user-plus"></i> Agregar nuevo paciente</button>
            <button class="btn-outline" id="export-list-btn" type="button"><i class="fas fa-file-export"></i>Exportar lista</button>
        </div>
    </header>

    @include('partials.frontend-alerts')

    <section class="stats-grid professional-patient-stats">
        <article class="stat-card"><span class="stat-icon"><i class="fas fa-users"></i></span><div><p>Total pacientes</p><strong>{{ $stats['total'] ?? $patients->count() }}</strong></div></article>
        <article class="stat-card"><span class="stat-icon stat-icon-success"><i class="fas fa-circle-check"></i></span><div><p>Activos</p><strong>{{ $stats['active'] ?? 0 }}</strong></div></article>
        <article class="stat-card"><span class="stat-icon stat-icon-warning"><i class="fas fa-list-check"></i></span><div><p>Tareas pendientes</p><strong>{{ $stats['pending_tasks'] ?? 0 }}</strong></div></article>
        <article class="stat-card"><span class="stat-icon stat-icon-danger"><i class="fas fa-triangle-exclamation"></i></span><div><p>Riesgo alto</p><strong>{{ $stats['high_risk'] ?? 0 }}</strong></div></article>
    </section>

    <section class="patients-toolbar patients-toolbar-search">
        <div class="patient-search-area">
            <label class="search-box" for="patient-search"><i class="fas fa-search"></i><input type="search" id="patient-search" placeholder="Buscar por nombre, correo, teléfono..." autocomplete="off" aria-expanded="false"></label>
            <div class="patient-search-results" id="patient-search-results" hidden role="listbox">
                @foreach($patients as $patient)
                    @php
                        $patientAppointments = $appointments->where('patient_id', $patient->id);
                        $nextAppointment = $patientAppointments->where('starts_at', '>=', now())->sortBy('starts_at')->first();
                        $lastAppointment = $patientAppointments->where('starts_at', '<', now())->sortByDesc('starts_at')->first();
                        $patientTasks = $tasks->where('patient_id', $patient->id);
                        $risk = $notes->where('patient_id', $patient->id)->contains(fn($note) => str_contains(mb_strtolower((string) $note->description), 'riesgo alto')) ? 'alto' : 'medio';
                    @endphp
                    <button class="patient-result {{ $loop->first ? 'active' : '' }}" type="button" data-target="detail-patient-{{ $patient->id }}" data-name="{{ $patient->nombre_completo }}" data-search="{{ $patient->nombre_completo }} {{ $patient->email }} {{ $patient->telefono }} {{ $patient->patientProfile?->motivo_consulta }} EXP-{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}" data-status="active" data-risk="{{ $risk }}" data-next-session="{{ optional($nextAppointment?->starts_at)->format('Y-m-d') }}" data-last-session="{{ optional($lastAppointment?->starts_at)->format('Y-m-d') }}">
                        <div class="result-avatar">{{ $patient->initials() }}</div>
                        <div class="result-info"><strong>{{ $patient->nombre_completo }}</strong><span>{{ $patient->email }} · {{ $patient->telefono ?: 'Sin teléfono' }}</span><small>{{ $patient->patientProfile?->motivo_consulta ?: 'Sin motivo registrado' }} · EXP-{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</small></div>
                    </button>
                @endforeach
                <div class="empty-search-results" id="empty-search-results" hidden><i class="fas fa-magnifying-glass"></i><p>No se encontraron pacientes.</p></div>
            </div>
        </div>
        <div class="toolbar-filters">
            <label>Estado<select id="status-filter"><option value="all">Todos</option><option value="active">Activos</option><option value="inactive">Inactivos</option></select></label>
            <label>Riesgo<select id="risk-filter"><option value="all">Todos</option><option value="medio">Medio</option><option value="alto">Alto</option></select></label>
            <label>Ordenar<select id="sort-filter"><option value="name">Nombre</option><option value="next-session">Próxima sesión</option><option value="last-session">Última sesión</option></select></label>
        </div>
    </section>

    <section class="patient-panel {{ $patients->isNotEmpty() ? 'has-patient' : '' }}" id="patient-panel">
        @if($patients->isEmpty())
            <div class="patient-empty-state"><i class="fas fa-user-friends"></i><h2>Aún no tienes pacientes vinculados</h2><p>También puedes agregar un paciente previo con el botón “Agregar nuevo paciente”.</p></div>
        @endif

        @foreach($patients as $patient)
            @php
                $profile = $patient->patientProfile;
                $emergency = $patient->emergencyContact;
                $patientAppointments = $appointments->where('patient_id', $patient->id)->sortByDesc('starts_at');
                $nextAppointment = $patientAppointments->filter(fn($a) => $a->starts_at && $a->starts_at->gte(now()))->sortBy('starts_at')->first();
                $lastAppointment = $patientAppointments->filter(fn($a) => $a->starts_at && $a->starts_at->lt(now()))->first();
                $patientTasks = $tasks->where('patient_id', $patient->id);
                $patientNotes = $notes->where('patient_id', $patient->id);
                $risk = $patientNotes->contains(fn($note) => str_contains(mb_strtolower((string) $note->description), 'riesgo alto')) ? 'alto' : 'medio';
            @endphp
            <div class="patient-detail patient-detail-panel" id="detail-patient-{{ $patient->id }}" @if(!$loop->first) hidden @endif>
                <section class="patient-hero">
                    <div class="patient-identity">
                        <div class="patient-avatar avatar-large">{{ $patient->initials() }}</div>
                        <div><span class="record-label">EXP-{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</span><h2>{{ $patient->nombre_completo }}</h2><p>{{ optional($patient->fecha_nacimiento)->age ? optional($patient->fecha_nacimiento)->age.' años · ' : '' }}{{ $profile?->terapia_previa === 'si' ? 'Terapia previa' : 'Terapia individual' }}</p><div class="patient-badges"><span class="status-badge status-active">Activo</span><span class="risk-badge risk-{{ $risk }}">Riesgo {{ $risk }}</span></div></div>
                    </div>
                    <div class="patient-dates"><article><span>Próxima sesión</span><strong>{{ $nextAppointment?->starts_at?->format('d/m/Y') ?? 'Sin programar' }}</strong></article><article><span>Última sesión</span><strong>{{ $lastAppointment?->starts_at?->format('d/m/Y') ?? 'Sin registro' }}</strong></article></div>
                </section>
                <section class="patient-layout">
                    <nav class="patient-tabs">
                        <button class="patient-tab active" data-tab="summary" type="button"><i class="fas fa-chart-pie"></i>Resumen</button>
                        <button class="patient-tab" data-tab="history" type="button"><i class="fas fa-notes-medical"></i>Historial clínico</button>
                        <button class="patient-tab" data-tab="sessions" type="button"><i class="fas fa-calendar-check"></i>Historial de sesiones</button>
                        <button class="patient-tab" data-tab="tasks" type="button"><i class="fas fa-list-check"></i>Tareas</button>
                        <button class="patient-tab" data-tab="notes" type="button"><i class="fas fa-note-sticky"></i>Notas</button>
                    </nav>
                    <div class="patient-content-area">
                        <section class="tab-panel active" data-panel="summary">
                            <div class="panel-heading"><div><h2>Resumen clínico</h2><p>Vista rápida del estado actual.</p></div></div>
                            <div class="summary-grid">
                                <article class="summary-card"><span>Motivo de consulta</span><p>{{ $profile?->motivo_consulta ?: 'No registrado' }}</p></article>
                                <article class="summary-card"><span>Objetivo actual</span><p>{{ $profile?->objetivos ?: 'No registrado' }}</p></article>
                                <article class="summary-card"><span>Teléfono</span><p>{{ $patient->telefono ?: 'No registrado' }}</p></article>
                                <article class="summary-card"><span>Correo</span><p>{{ $patient->email }}</p></article>
                                <article class="summary-card"><span>Contacto emergencia</span><p>{{ $emergency ? $emergency->nombre.' · '.$emergency->telefono : 'No registrado' }}</p></article>
                                <article class="summary-card"><span>Medicación actual</span><p>{{ $profile?->medicacion_actual ?: 'No registrada' }}</p></article>
                                <article class="summary-card"><span>Estado</span><p>Activo</p></article>
                            </div>
                        </section>
                        <section class="tab-panel" data-panel="history">
                            @php
                                $history = (array)($profile?->clinical_history ?? []);
                                $attachments = (array)($profile?->clinical_attachments ?? []);
                                $h = fn($key, $fallback = '') => old($key, data_get($history, $key, $fallback));
                            @endphp
                            <form class="clinical-history-form" method="POST" action="{{ route('profesional.pacientes.store') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="form_type" value="clinical_history">
                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                <div class="panel-heading">
                                    <div>
                                        <h2>Historial clínico</h2>
                                        <p>Expediente clínico psicológico con datos de identificación, interrogatorio, evaluación, diagnóstico, riesgo y plan terapéutico.</p>
                                    </div>
                                    <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Guardar historial</button>
                                </div>

                                <div class="history-tabs" role="tablist" aria-label="Secciones del historial clínico">
                                    <button class="history-tab active" type="button" data-history-tab="identificacion">Identificación</button>
                                    <button class="history-tab" type="button" data-history-tab="motivo">Motivo de consulta</button>
                                    <button class="history-tab" type="button" data-history-tab="antecedentes">Antecedentes</button>
                                    <button class="history-tab" type="button" data-history-tab="estado-general">Estado general</button>
                                    <button class="history-tab" type="button" data-history-tab="evaluacion">Evaluación psicológica</button>
                                    <button class="history-tab" type="button" data-history-tab="diagnostico">Diagnóstico</button>
                                    <button class="history-tab" type="button" data-history-tab="riesgo">Riesgo clínico</button>
                                    <button class="history-tab" type="button" data-history-tab="plan">Plan terapéutico</button>
                                    <button class="history-tab" type="button" data-history-tab="adjuntos">Archivos adjuntos</button>
                                </div>

                                <fieldset class="history-module active" data-history-section="identificacion">
                                    <legend>Identificación del paciente</legend>
                                    <div class="history-content">
                                        <label class="history-field"><strong>Nombre completo</strong><input type="text" name="paciente_nombre" value="{{ $h('paciente_nombre', $patient->nombre_completo) }}"></label>
                                        <label class="history-field"><strong>Edad</strong><input type="number" min="0" name="paciente_edad" value="{{ $h('paciente_edad', optional($patient->fecha_nacimiento)->age) }}"></label>
                                        <label class="history-field"><strong>Fecha de nacimiento</strong><input type="date" name="paciente_fecha_nacimiento" value="{{ $h('paciente_fecha_nacimiento', optional($patient->fecha_nacimiento)->format('Y-m-d')) }}"></label>
                                        <label class="history-field"><strong>Sexo</strong><input type="text" name="paciente_sexo" value="{{ $h('paciente_sexo', $patient->genero) }}" placeholder="Sexo registrado o no especificado"></label>
                                        <label class="history-field"><strong>Género</strong><input type="text" name="paciente_genero" value="{{ $h('paciente_genero', $patient->genero) }}"></label>
                                        <label class="history-field"><strong>CURP</strong><input type="text" name="paciente_curp" maxlength="18" value="{{ $h('paciente_curp') }}" placeholder="CURP del paciente"></label>
                                        <label class="history-field"><strong>Teléfono</strong><input type="tel" name="paciente_telefono" value="{{ $h('paciente_telefono', $patient->telefono) }}"></label>
                                        <label class="history-field"><strong>Correo electrónico</strong><input type="email" name="paciente_correo" value="{{ $h('paciente_correo', $patient->email) }}"></label>
                                        <label class="history-field form-full"><strong>Domicilio</strong><textarea name="paciente_domicilio" rows="2">{{ $h('paciente_domicilio', $profile?->domicilio) }}</textarea></label>
                                        <label class="history-field"><strong>Ocupación / escuela</strong><input type="text" name="paciente_ocupacion" value="{{ $h('paciente_ocupacion', $profile?->ocupacion) }}"></label>
                                        <label class="history-field"><strong>Estado civil</strong><input type="text" name="paciente_estado_civil" value="{{ $h('paciente_estado_civil', $profile?->estado_civil) }}"></label>
                                        <label class="history-field"><strong>Contacto de emergencia</strong><input type="text" name="contacto_emergencia_nombre" value="{{ $h('contacto_emergencia_nombre', $emergency?->nombre) }}"></label>
                                        <label class="history-field"><strong>Teléfono de emergencia</strong><input type="tel" name="contacto_emergencia_telefono" value="{{ $h('contacto_emergencia_telefono', $emergency?->telefono) }}"></label>
                                        <label class="history-field"><strong>Parentesco del contacto</strong><input type="text" name="contacto_emergencia_parentesco" value="{{ $h('contacto_emergencia_parentesco', $emergency?->relacion) }}"></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="motivo">
                                    <legend>Motivo de consulta y padecimiento actual</legend>
                                    <div class="history-content">
                                        <label class="history-field form-full"><strong>Motivo principal de consulta</strong><textarea name="motivo_consulta" rows="3">{{ $h('motivo_consulta', $profile?->motivo_consulta) }}</textarea></label>
                                        <label class="history-field"><strong>Fecha aproximada de inicio</strong><input type="text" name="inicio_padecimiento" value="{{ $h('inicio_padecimiento') }}"></label>
                                        <label class="history-field"><strong>Curso del problema</strong><input type="text" name="curso_padecimiento" value="{{ $h('curso_padecimiento') }}" placeholder="Agudo, subagudo, crónico, recurrente"></label>
                                        <label class="history-field form-full"><strong>Descripción del problema actual</strong><textarea name="descripcion_padecimiento_actual" rows="3">{{ $h('descripcion_padecimiento_actual') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Impacto en la vida diaria</strong><textarea name="impacto_funcional" rows="3">{{ $h('impacto_funcional') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Objetivos expresados por el paciente</strong><textarea name="objetivos_paciente" rows="3">{{ $h('objetivos_paciente', $profile?->objetivos) }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="antecedentes">
                                    <legend>Antecedentes</legend>
                                    <div class="history-content">
                                        <label class="history-field form-full"><strong>Antecedentes heredofamiliares</strong><textarea name="antecedentes_heredofamiliares" rows="3">{{ $h('antecedentes_heredofamiliares') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Antecedentes personales patológicos</strong><textarea name="antecedentes_personales_patologicos" rows="3">{{ $h('antecedentes_personales_patologicos', $profile?->antecedentes) }}</textarea></label>
                                        <label class="history-field form-full"><strong>Antecedentes personales no patológicos</strong><textarea name="antecedentes_personales_no_patologicos" rows="3">{{ $h('antecedentes_personales_no_patologicos') }}</textarea></label>
                                        <label class="history-field"><strong>Alergias</strong><input type="text" name="alergias" value="{{ $h('alergias', $profile?->alergias) }}"></label>
                                        <label class="history-field"><strong>Medicación actual</strong><input type="text" name="medicacion_actual" value="{{ $h('medicacion_actual', $profile?->medicacion_actual) }}"></label>
                                        <label class="history-field"><strong>Consumo de sustancias</strong><input type="text" name="consumo_sustancias" value="{{ $h('consumo_sustancias') }}"></label>
                                        <label class="history-field"><strong>Hospitalizaciones previas</strong><input type="text" name="hospitalizaciones_previas" value="{{ $h('hospitalizaciones_previas') }}"></label>
                                        <label class="history-field form-full"><strong>Antecedentes psicológicos / psiquiátricos</strong><textarea name="antecedentes_psicologicos" rows="3">{{ $h('antecedentes_psicologicos') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Dinámica familiar y red de apoyo</strong><textarea name="dinamica_familiar_red_apoyo" rows="3">{{ $h('dinamica_familiar_red_apoyo') }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="estado-general">
                                    <legend>Interrogatorio y estado general</legend>
                                    <div class="history-content">
                                        <label class="history-field"><strong>Sueño</strong><input type="text" name="sueno" value="{{ $h('sueno') }}"></label>
                                        <label class="history-field"><strong>Apetito</strong><input type="text" name="apetito" value="{{ $h('apetito') }}"></label>
                                        <label class="history-field"><strong>Energía</strong><input type="text" name="energia" value="{{ $h('energia') }}"></label>
                                        <label class="history-field"><strong>Síntomas físicos relevantes</strong><input type="text" name="sintomas_fisicos" value="{{ $h('sintomas_fisicos') }}"></label>
                                        <label class="history-field"><strong>Signos vitales, si aplica</strong><input type="text" name="signos_vitales" value="{{ $h('signos_vitales') }}" placeholder="TA, FC, FR, temperatura"></label>
                                        <label class="history-field"><strong>Peso y talla, si aplica</strong><input type="text" name="peso_talla" value="{{ $h('peso_talla') }}" placeholder="Peso / talla"></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="evaluacion">
                                    <legend>Evaluación psicológica y examen mental</legend>
                                    <div class="history-content">
                                        <label class="history-field"><strong>Habitus exterior</strong><input type="text" name="habitus_exterior" value="{{ $h('habitus_exterior') }}"></label>
                                        <label class="history-field"><strong>Actitud durante entrevista</strong><input type="text" name="actitud_entrevista" value="{{ $h('actitud_entrevista') }}"></label>
                                        <label class="history-field"><strong>Orientación</strong><input type="text" name="orientacion" value="{{ $h('orientacion') }}"></label>
                                        <label class="history-field"><strong>Atención y concentración</strong><input type="text" name="atencion_concentracion" value="{{ $h('atencion_concentracion') }}"></label>
                                        <label class="history-field"><strong>Memoria</strong><input type="text" name="memoria" value="{{ $h('memoria') }}"></label>
                                        <label class="history-field"><strong>Lenguaje</strong><input type="text" name="lenguaje" value="{{ $h('lenguaje') }}"></label>
                                        <label class="history-field"><strong>Estado de ánimo</strong><input type="text" name="estado_animo" value="{{ $h('estado_animo') }}"></label>
                                        <label class="history-field"><strong>Afecto</strong><input type="text" name="afecto" value="{{ $h('afecto') }}"></label>
                                        <label class="history-field form-full"><strong>Curso y contenido del pensamiento</strong><textarea name="pensamiento" rows="3">{{ $h('pensamiento') }}</textarea></label>
                                        <label class="history-field"><strong>Percepción</strong><input type="text" name="percepcion" value="{{ $h('percepcion') }}"></label>
                                        <label class="history-field"><strong>Juicio</strong><input type="text" name="juicio" value="{{ $h('juicio') }}"></label>
                                        <label class="history-field"><strong>Insight</strong><input type="text" name="insight" value="{{ $h('insight') }}"></label>
                                        <label class="history-field form-full"><strong>Pruebas psicológicas aplicadas</strong><textarea name="pruebas_aplicadas" rows="3">{{ $h('pruebas_aplicadas') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Resultados e interpretación</strong><textarea name="resultados_interpretacion" rows="3">{{ $h('resultados_interpretacion') }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="diagnostico">
                                    <legend>Diagnóstico, problemas clínicos y pronóstico</legend>
                                    <div class="history-content">
                                        <label class="history-field form-full"><strong>Problemas clínicos identificados</strong><textarea name="problemas_clinicos" rows="3">{{ $h('problemas_clinicos') }}</textarea></label>
                                        <label class="history-field"><strong>Impresión diagnóstica</strong><input type="text" name="impresion_diagnostica" value="{{ $h('impresion_diagnostica') }}"></label>
                                        <label class="history-field"><strong>Código CIE / DSM, si aplica</strong><input type="text" name="codigo_diagnostico" value="{{ $h('codigo_diagnostico') }}"></label>
                                        <label class="history-field form-full"><strong>Diagnóstico diferencial</strong><textarea name="diagnostico_diferencial" rows="3">{{ $h('diagnostico_diferencial') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Pronóstico</strong><textarea name="pronostico" rows="3">{{ $h('pronostico') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Observaciones diagnósticas</strong><textarea name="observaciones_diagnosticas" rows="3">{{ $h('observaciones_diagnosticas') }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="riesgo">
                                    <legend>Riesgo clínico y plan de seguridad</legend>
                                    <div class="history-content">
                                        <label class="history-field"><strong>Nivel general de riesgo</strong><input type="text" name="nivel_riesgo_general" value="{{ $h('nivel_riesgo_general') }}" placeholder="Bajo, medio, alto o crítico"></label>
                                        <label class="history-field"><strong>Riesgo suicida</strong><input type="text" name="riesgo_suicida" value="{{ $h('riesgo_suicida') }}"></label>
                                        <label class="history-field"><strong>Riesgo de autolesión</strong><input type="text" name="riesgo_autolesion" value="{{ $h('riesgo_autolesion') }}"></label>
                                        <label class="history-field"><strong>Riesgo hacia terceros</strong><input type="text" name="riesgo_terceros" value="{{ $h('riesgo_terceros') }}"></label>
                                        <label class="history-field"><strong>Ideación suicida actual</strong><input type="text" name="ideacion_suicida_actual" value="{{ $h('ideacion_suicida_actual') }}"></label>
                                        <label class="history-field"><strong>Intentos previos</strong><input type="text" name="intentos_previos" value="{{ $h('intentos_previos') }}"></label>
                                        <label class="history-field form-full"><strong>Factores de riesgo identificados</strong><textarea name="factores_riesgo" rows="3">{{ $h('factores_riesgo') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Factores protectores</strong><textarea name="factores_protectores" rows="3">{{ $h('factores_protectores') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Plan de seguridad</strong><textarea name="plan_seguridad" rows="3">{{ $h('plan_seguridad') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Canalización o interconsulta, si aplica</strong><textarea name="canalizacion_interconsulta" rows="3">{{ $h('canalizacion_interconsulta') }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="plan">
                                    <legend>Plan terapéutico</legend>
                                    <div class="history-content">
                                        <label class="history-field form-full"><strong>Objetivos generales</strong><textarea name="objetivos_generales" rows="3">{{ $h('objetivos_generales') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Objetivos específicos</strong><textarea name="objetivos_especificos" rows="3">{{ $h('objetivos_especificos') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Intervenciones terapéuticas</strong><textarea name="intervenciones_terapeuticas" rows="3">{{ $h('intervenciones_terapeuticas') }}</textarea></label>
                                        <label class="history-field"><strong>Enfoque terapéutico</strong><input type="text" name="enfoque_terapeutico" value="{{ $h('enfoque_terapeutico') }}"></label>
                                        <label class="history-field"><strong>Frecuencia de sesiones</strong><input type="text" name="frecuencia_sesiones" value="{{ $h('frecuencia_sesiones') }}"></label>
                                        <label class="history-field"><strong>Duración estimada</strong><input type="text" name="duracion_estimada" value="{{ $h('duracion_estimada') }}"></label>
                                        <label class="history-field form-full"><strong>Indicaciones terapéuticas</strong><textarea name="indicaciones_terapeuticas" rows="3">{{ $h('indicaciones_terapeuticas') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Tareas para casa</strong><textarea name="tareas_para_casa" rows="3">{{ $h('tareas_para_casa') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Indicadores de progreso</strong><textarea name="indicadores_progreso" rows="3">{{ $h('indicadores_progreso') }}</textarea></label>
                                        <label class="history-field form-full"><strong>Criterios de alta o cierre</strong><textarea name="criterios_alta" rows="3">{{ $h('criterios_alta') }}</textarea></label>
                                    </div>
                                </fieldset>

                                <fieldset class="history-module" data-history-section="adjuntos">
                                    <legend>Archivos adjuntos del paciente</legend>
                                    <div class="attachments-panel">
                                        <div class="attachment-upload-card">
                                            <label class="history-field form-full"><strong>Subir archivos relacionados con el paciente</strong><input type="file" name="archivos_paciente[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xlsx,.xls"></label>
                                            <label class="history-field form-full"><strong>Descripción del archivo</strong><textarea name="descripcion_documento_adjunto" rows="3" placeholder="Ej. Consentimiento informado, evaluación aplicada, material terapéutico o documento recibido."></textarea></label>
                                        </div>
                                        <div class="attached-files-list">
                                            <h3>Archivos adjuntos registrados</h3>
                                            @forelse($attachments as $index => $attachment)
                                                <article class="attached-file-card">
                                                    <div class="attached-file-icon"><i class="fas fa-file-medical"></i></div>
                                                    <div><strong>{{ $attachment['nombre_original'] ?? 'Adjunto clínico' }}</strong><p>{{ $attachment['descripcion'] ?? 'Sin descripción' }}</p><small>{{ $attachment['uploaded_at'] ?? '' }} · {{ $attachment['mime'] ?? 'archivo' }}</small></div>
                                                    <div class="attached-file-actions"><a class="icon-btn" target="_blank" href="{{ route('profesional.pacientes.adjuntos.view', [$patient, $index]) }}" title="Ver archivo"><i class="fas fa-eye"></i></a></div>
                                                </article>
                                            @empty
                                                <div class="backend-empty">No hay archivos adjuntos registrados para este expediente.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </fieldset>
                            </form>
                        </section>
                        <section class="tab-panel" data-panel="sessions">
                            <div class="panel-heading"><div><h2>Historial de sesiones</h2><p>Sesiones reales registradas con este paciente.</p></div></div>
                            <div class="session-history-list session-history-readonly">
                                @forelse($patientAppointments as $appointment)
                                    <article class="session-record-card"><div class="session-record-header"><div><span class="record-label">ID {{ $appointment->id }}</span><h3>{{ $appointment->reason }}</h3><p>{{ optional($appointment->starts_at)->format('d/m/Y H:i') }} · {{ $appointment->modality }} · {{ $appointment->payment_status }}</p></div><span class="item-status">{{ ucfirst($appointment->status) }}</span></div></article>
                                @empty
                                    <div class="backend-empty">No hay sesiones registradas.</div>
                                @endforelse
                            </div>
                        </section>
                        <section class="tab-panel" data-panel="tasks">
                            <div class="panel-heading"><div><h2>Tareas terapéuticas</h2><p>Actividades entre sesiones y entregas revisables.</p></div></div>
                            <form class="clinical-history-form compact-task-form" method="POST" action="{{ route('profesional.pacientes.store') }}">@csrf<input type="hidden" name="kind" value="task"><input type="hidden" name="patient_id" value="{{ $patient->id }}"><div class="form-row two"><label>Título<input name="title" required></label><label>Entrega<input type="date" name="date"></label></div><label>Descripción<textarea name="description" required></textarea></label><label>Repetición<input name="repeat" placeholder="Diaria, semanal, una vez"></label><button class="btn-primary" type="submit"><i class="fas fa-plus"></i>Asignar tarea</button></form>
                            <div class="items-list tasks-content">
                                @forelse($patientTasks as $task)
                                    <article class="item-card task-item-card"><div class="item-card-header"><div><h3>{{ $task->title }}</h3><small>Vence: {{ optional($task->due_date)->format('d/m/Y') ?: 'Sin fecha' }}</small></div><span class="item-status">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span></div><p>{{ $task->description }}</p>@if($task->follow_up)<details><summary>Respuesta del paciente</summary><p>{{ $task->follow_up }}</p>@if($task->evidence_file_path)<a target="_blank" href="{{ route('profesional.tareas.pdf', $task) }}">Ver PDF adjunto</a>@endif</details>@endif @if($task->status === 'entregada')<form method="POST" action="{{ route('profesional.tareas.review', $task) }}" class="review-form">@csrf<label>Comentarios<textarea name="review_feedback"></textarea></label><button class="btn-primary-small" name="review_action" value="aprobada">Aprobar</button><button class="btn-outline request-danger btn-sm" name="review_action" value="requiere_cambios">Solicitar cambios</button></form>@endif</article>
                                @empty
                                    <div class="backend-empty">No hay tareas asignadas.</div>
                                @endforelse
                            </div>
                        </section>
                        <section class="tab-panel" data-panel="notes">
                            <div class="panel-heading"><div><h2>Notas profesionales</h2><p>Notas clínicas privadas de tu seguimiento.</p></div></div>
                            <form class="clinical-history-form compact-task-form" method="POST" action="{{ route('profesional.pacientes.store') }}">@csrf<input type="hidden" name="kind" value="note"><input type="hidden" name="patient_id" value="{{ $patient->id }}"><div class="form-row two"><label>Título<input name="title" required></label><label>Fecha<input type="date" name="date" value="{{ today()->toDateString() }}"></label></div><label>Tipo<input name="type" placeholder="Clínica, seguimiento, riesgo..."></label><label>Descripción<textarea name="description" required></textarea></label><button class="btn-primary" type="submit"><i class="fas fa-save"></i>Guardar nota</button></form>
                            <div class="items-list tasks-content">
                                @forelse($patientNotes as $note)
                                    <article class="item-card"><div class="item-card-header"><div><h3>{{ $note->title }}</h3><small>{{ optional($note->note_date)->format('d/m/Y') }} · {{ $note->type }}</small></div></div><p>{{ $note->description }}</p></article>
                                @empty
                                    <div class="backend-empty">No hay notas clínicas registradas.</div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        @endforeach
    </section>
</main>
<div class="iris-action-modal-backdrop" id="new-patient-modal" hidden>
    <section class="iris-action-modal" role="dialog" aria-modal="true" aria-labelledby="new-patient-title">
        <button class="iris-modal-close" type="button" data-close-new-patient aria-label="Cerrar">×</button>
        <p class="section-kicker"><i class="fas fa-user-plus"></i> Digitalizar seguimiento</p>
        <h2 id="new-patient-title">Agregar nuevo paciente</h2>
        <p class="muted-copy">Usa este formulario para registrar pacientes que ya atendías fuera de IRIS y comenzar su seguimiento digital.</p>
        <form method="POST" action="{{ route('profesional.pacientes.store') }}" class="modal-form-grid">
            @csrf
            <input type="hidden" name="form_type" value="new_patient">
            <div class="form-row two">
                <label>Nombre<input name="nombre" required maxlength="120"></label>
                <label>Apellidos<input name="apellidos" required maxlength="160"></label>
            </div>
            <div class="form-row two">
                <label>Correo<input type="email" name="email" placeholder="opcional@correo.com"></label>
                <label>Teléfono<input name="telefono" maxlength="30"></label>
            </div>
            <label>Fecha de nacimiento<input type="date" name="fecha_nacimiento"></label>
            <label>Motivo de consulta<textarea name="motivo_consulta" rows="3" placeholder="Motivo principal del seguimiento"></textarea></label>
            <label>Objetivo actual<textarea name="objetivos" rows="3" placeholder="Objetivo terapéutico actual"></textarea></label>
            <div class="form-row two">
                <label>Contacto emergencia<input name="emergencia_nombre" maxlength="180"></label>
                <label>Teléfono emergencia<input name="emergencia_telefono" maxlength="30"></label>
            </div>
            <div class="modal-actions"><button class="btn-outline" type="button" data-close-new-patient>Cancelar</button><button class="btn-primary" type="submit">Agregar paciente</button></div>
        </form>
    </section>
</div>
<script>
const searchInput = document.getElementById('patient-search');
const resultsBox = document.getElementById('patient-search-results');
const emptyResults = document.getElementById('empty-search-results');
const resultButtons = Array.from(document.querySelectorAll('.patient-result'));
function showPatient(target){document.querySelectorAll('.patient-detail-panel').forEach(p=>p.hidden=true);document.getElementById(target)?.removeAttribute('hidden');resultButtons.forEach(b=>b.classList.toggle('active', b.dataset.target===target));}
searchInput?.addEventListener('focus',()=>{resultsBox.hidden=false});
searchInput?.addEventListener('input',()=>{const q=searchInput.value.toLowerCase().trim();let shown=0;resultButtons.forEach(btn=>{const ok=!q || (btn.dataset.search||'').toLowerCase().includes(q);btn.style.display=ok?'flex':'none'; if(ok) shown++;}); if(emptyResults) emptyResults.hidden=shown>0; resultsBox.hidden=false;});
resultButtons.forEach(btn=>btn.addEventListener('click',()=>{showPatient(btn.dataset.target);resultsBox.hidden=true;searchInput.value=btn.dataset.name||'';}));
document.addEventListener('click',(e)=>{if(!e.target.closest('.patient-search-area')) resultsBox.hidden=true;});
document.querySelectorAll('.patient-detail-panel').forEach(panel=>{
  panel.querySelectorAll('.patient-tab').forEach(tab=>tab.addEventListener('click',()=>{panel.querySelectorAll('.patient-tab').forEach(t=>t.classList.remove('active'));tab.classList.add('active');panel.querySelectorAll('.tab-panel').forEach(p=>p.classList.toggle('active', p.dataset.panel===tab.dataset.tab));}));
  panel.querySelectorAll('.history-tab').forEach(tab=>tab.addEventListener('click',()=>{const target=tab.dataset.historyTab; const form=tab.closest('.clinical-history-form'); form?.querySelectorAll('.history-tab').forEach(t=>t.classList.remove('active')); tab.classList.add('active'); form?.querySelectorAll('.history-module').forEach(section=>section.classList.toggle('active', section.dataset.historySection===target));}));
});
const newPatientModal = document.getElementById('new-patient-modal');
document.getElementById('open-new-patient-modal')?.addEventListener('click', () => newPatientModal?.removeAttribute('hidden'));
function closeNewPatientModal(){ newPatientModal?.setAttribute('hidden', 'hidden'); }
document.querySelectorAll('[data-close-new-patient]').forEach(btn => btn.addEventListener('click', closeNewPatientModal));
newPatientModal?.addEventListener('click', event => { if (event.target === newPatientModal) closeNewPatientModal(); });
document.getElementById('export-list-btn')?.addEventListener('click',()=>window.print());
</script>
</body>
</html>
