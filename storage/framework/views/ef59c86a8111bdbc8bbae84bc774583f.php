<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta name="user-role" content="<?php echo e(auth()->user()->rol); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil profesional | IRIS</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/perfil-psicologo.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body" data-role="<?php echo e(auth()->user()->rol); ?>">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-psicologo.js')); ?>"></script>
<main class="dashboard-main professional-profile-page">
  <?php echo $__env->make('partials.profesional-header', [
    'title'=>'Perfil del '.($user->rol === 'psiquiatra' ? 'psiquiatra' : 'psicólogo'),
    'subtitle'=>'Actualiza tu información profesional y revisa cómo se verá tu perfil público para los pacientes.'
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php
    $profile = $user->professionalProfile;
    $diasAtencion = old('dias_atencion', $profile?->dias_atencion ?? []);
    $formacionAcademica = old('formacion_academica_text', implode("\n", (array) ($profile?->formacion_academica ?? [])));
    $especialidadesTexto = old('especialidades_text', implode(', ', (array) ($profile?->especialidades ?? [])));
    $especialidades = collect((array)($profile?->especialidades ?? []))->filter()->values();
    $formacion = collect((array)($profile?->formacion_academica ?? []))->filter()->values();
    $areas = collect((array)($profile?->areas ?? []))->filter()->values();
    $enfoques = old('enfoques', $profile?->enfoques ?? []);
    $poblaciones = old('poblaciones', $profile?->poblaciones ?? []);
    $areasSeleccionadas = old('areas', $profile?->areas ?? []);
    $documentos = (array)($profile?->documentos ?? []);
    $initials = collect(explode(' ', trim($user->nombre_completo ?: $user->name ?: $user->email)))->filter()->take(2)->map(fn($part)=>mb_substr($part,0,1))->implode('');
    $statusLabel = match($user->professional_status) {
      'approved' => 'Perfil aprobado',
      'pending' => 'En revisión administrativa',
      'rejected' => 'Perfil rechazado',
      default => 'Perfil incompleto',
    };
    $statusClass = match($user->professional_status) {
      'approved' => 'ok',
      'pending' => 'warn',
      'rejected' => 'danger',
      default => 'muted',
    };
    $diasMap = ['lunes'=>'L','martes'=>'M','miércoles'=>'M','jueves'=>'J','viernes'=>'V','sábado'=>'S','domingo'=>'D'];
    $tarifa = $profile?->costo_min ? '$'.number_format((float)$profile->costo_min,0).' MXN' : 'Pendiente';
    $generos = [''=>'Seleccionar…','femenino'=>'Femenino','masculino'=>'Masculino','no-binario'=>'No binario','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
    $relacionesEmergencia = ['madre'=>'Madre','padre'=>'Padre','hermano/a'=>'Hermano/a','pareja'=>'Pareja','amigo/a'=>'Amigo/a','familiar'=>'Familiar','tutor/a'=>'Tutor/a','otro'=>'Otro'];
    $professionalPrefix = $user->rol === 'psiquiatra' ? 'Dr.' : 'Psic.';
  ?>

  <section class="profile-hero-card dash-card">
    <div class="profile-hero-left">
      <div class="profile-avatar-xl"><?php echo e($initials ?: 'IR'); ?></div>
      <div>
        <h2><?php echo e($professionalPrefix); ?> <?php echo e($user->nombre_completo ?: $user->name); ?></h2>
        <p><?php echo e($profile?->especialidad_principal ?: ucfirst($user->rol).' sin especialidad pública registrada'); ?></p>
        <div class="profile-status-row">
          <span class="profile-status-chip <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
          <?php if($profile?->updated_at): ?><small>Última actualización: <?php echo e($profile->updated_at->format('d/m/Y H:i')); ?></small><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="profile-tabs-switch" role="tablist" aria-label="Secciones de perfil profesional">
      <button type="button" class="profile-tab-btn active" data-profile-tab="edit">☊ Mi perfil</button>
      <button type="button" class="profile-tab-btn" data-profile-tab="public">◉ Vista pública</button>
      <button type="button" class="profile-tab-btn" data-profile-tab="billing">▣ Suscripción y pagos</button>
    </div>
  </section>

  <section class="profile-tab-panel active" data-profile-panel="edit">
    <form method="POST" action="<?php echo e(route('profesional.perfil.update')); ?>" enctype="multipart/form-data" class="dash-card professional-profile-form-v3">
      <?php echo csrf_field(); ?>
      <div class="card-top">
        <div>
          <h2>Mi perfil profesional</h2>
          <p class="card-subtitle">Guarda avances cuando quieras. Cuando completes todos los datos y documentos requeridos, se enviará automáticamente al administrador para validación.</p>
        </div>
        <button class="btn-primary" type="submit">Guardar cambios</button>
      </div>

      <div class="profile-completion-note">
        <strong>Completa tu información profesional.</strong>
        <span>Guarda tus avances cuando quieras. Cuando termines los datos requeridos y subas tus documentos, tu perfil se enviará automáticamente al administrador para revisión.</span>
      </div>

      <div class="profile-form-section">
        <h3>Datos personales</h3>
        <div class="backend-form-grid two">
          <label class="backend-field">Nombre(s)<input type="text" name="nombre" value="<?php echo e(old('nombre',$user->nombre)); ?>" placeholder="Ej. Sofía" autocomplete="given-name"></label>
          <label class="backend-field">Apellidos<input type="text" name="apellidos" value="<?php echo e(old('apellidos',$user->apellidos)); ?>" placeholder="Ej. Hernández López" autocomplete="family-name"></label>
          <label class="backend-field">Teléfono celular<input type="tel" name="telefono" value="<?php echo e(old('telefono',$user->telefono)); ?>" placeholder="55 1234 5678" autocomplete="tel" inputmode="tel"></label>
          <label class="backend-field">Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="<?php echo e(old('fecha_nacimiento', optional($user->fecha_nacimiento)->format('Y-m-d'))); ?>"></label>
          <label class="backend-field">Género<select name="genero">
            <?php $__currentLoopData = $generos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($value); ?>" <?php if(old('genero',$user->genero)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select></label>
          <label class="backend-field">Tipo profesional<input type="text" value="<?php echo e(ucfirst($user->rol)); ?>" disabled></label>
        </div>
      </div>

      <div class="profile-form-section">
        <h3>Contacto de emergencia</h3>
        <div class="backend-form-grid three">
          <label class="backend-field">Nombre completo<input type="text" name="emergencia_nombre" value="<?php echo e(old('emergencia_nombre',$user->emergencyContact?->nombre)); ?>" placeholder="Ej. Laura Hernández"></label>
          <label class="backend-field">Relación<select name="emergencia_relacion">
            <option value="">Seleccionar…</option>
            <?php $__currentLoopData = $relacionesEmergencia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($value); ?>" <?php if(old('emergencia_relacion',$user->emergencyContact?->relacion)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select></label>
          <label class="backend-field">Teléfono<input type="tel" name="emergencia_telefono" value="<?php echo e(old('emergencia_telefono',$user->emergencyContact?->telefono)); ?>" placeholder="55 1234 5678" inputmode="tel"></label>
        </div>
      </div>

      <div class="profile-form-section">
        <h3>Credenciales y formación</h3>
        <div class="backend-form-grid two">
          <label class="backend-field">Título profesional<input name="titulo_profesional" value="<?php echo e(old('titulo_profesional',$profile?->titulo_profesional)); ?>" placeholder="Ej. Licenciatura en Psicología"></label>
          <label class="backend-field">Especialidad principal<input name="especialidad_principal" value="<?php echo e(old('especialidad_principal',$profile?->especialidad_principal)); ?>" placeholder="Ej. Psicología clínica y manejo emocional"></label>
          <label class="backend-field">Cédula profesional<input type="text" name="cedula_profesional" value="<?php echo e(old('cedula_profesional',$profile?->cedula_profesional)); ?>" placeholder="Ej. 12345678" inputmode="numeric"></label>
          <label class="backend-field">Cédula de especialidad<input type="text" name="cedula_especialidad" value="<?php echo e(old('cedula_especialidad',$profile?->cedula_especialidad)); ?>" placeholder="Ej. 87654321" inputmode="numeric"></label>
          <label class="backend-field">Institución<input type="text" name="institucion" value="<?php echo e(old('institucion',$profile?->institucion)); ?>" placeholder="Ej. Universidad Nacional Autónoma de México"></label>
          <label class="backend-field">Posgrado<input type="text" name="posgrado" value="<?php echo e(old('posgrado',$profile?->posgrado)); ?>" placeholder="Ej. Maestría en terapia cognitivo-conductual"></label>
          <label class="backend-field">Años de experiencia<input type="number" min="0" max="80" name="experiencia_anios" value="<?php echo e(old('experiencia_anios',$profile?->experiencia_anios)); ?>" placeholder="Ej. 6"></label>
          <label class="backend-field">Asociaciones profesionales<input type="text" name="asociaciones" value="<?php echo e(old('asociaciones',$profile?->asociaciones)); ?>" placeholder="Ej. Colegio Mexicano de Psicología"></label>
          <label class="backend-field full">Formación académica pública<textarea name="formacion_academica_text" rows="4" placeholder="Una línea por grado, institución o certificación."><?php echo e($formacionAcademica); ?></textarea></label>
          <label class="backend-field full">Documentos de respaldo PDF/imagen<input type="file" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"><small>Documentos cargados: <?php echo e(count($documentos)); ?></small></label>
        </div>
      </div>

      <div class="profile-form-section">
        <h3>Información pública de consulta</h3>
        <div class="backend-form-grid two">
          <label class="backend-field full">Acerca de mí<textarea name="biografia" rows="5" placeholder="Describe tu enfoque, experiencia y estilo de acompañamiento."><?php echo e(old('biografia',$profile?->biografia)); ?></textarea></label>
          <label class="backend-field full">Especialidades visibles<input type="text" name="especialidades_text" value="<?php echo e($especialidadesTexto); ?>" placeholder="Ej. Ansiedad, depresión, estrés laboral, autoestima"></label>
          <label class="backend-field">Modalidad<select name="modalidad">
            <?php $__currentLoopData = ['ambas'=>'Presencial y en línea','online'=>'En línea','videollamada'=>'Videollamada','presencial'=>'Presencial','hibrida'=>'Híbrida']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($value); ?>" <?php if(old('modalidad',$profile?->modalidad)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select></label>
          <label class="backend-field">Ubicación<input type="text" name="ubicacion" value="<?php echo e(old('ubicacion',$profile?->ubicacion)); ?>" placeholder="Ej. Ciudad de México o atención en línea"></label>
          <label class="backend-field">Idiomas<input type="text" name="idiomas" value="<?php echo e(old('idiomas',$profile?->idiomas)); ?>" placeholder="Ej. Español, inglés"></label>
          <label class="backend-field">Duración de sesión<input type="number" min="30" max="180" step="5" name="duracion_sesion" value="<?php echo e(old('duracion_sesion',$profile?->duracion_sesion ?? 50)); ?>" placeholder="Ej. 50"></label>
          <label class="backend-field">Tarifa por sesión<input type="number" min="0" step="50" name="costo_min" value="<?php echo e(old('costo_min',$profile?->costo_min)); ?>" placeholder="Ej. 800"></label>
          <label class="backend-field">Tarifa máxima opcional<input type="number" min="0" step="50" name="costo_max" value="<?php echo e(old('costo_max',$profile?->costo_max)); ?>" placeholder="Ej. 1200"></label>
          <label class="backend-field full">Servicios<textarea name="servicios" rows="3" placeholder="Ej. Terapia individual, orientación inicial, seguimiento terapéutico."><?php echo e(old('servicios',$profile?->servicios)); ?></textarea></label>
          <label class="backend-field full">Presentación profesional<textarea name="presentacion" rows="3" placeholder="Ej. Mensaje breve para explicar cómo trabajas con tus pacientes."><?php echo e(old('presentacion',$profile?->presentacion)); ?></textarea></label>
        </div>
        <div class="backend-field profile-days-field">
          <span>Días de atención visibles</span>
          <div class="profile-days-admin">
            <?php $__currentLoopData = ['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <label><input type="checkbox" name="dias_atencion[]" value="<?php echo e($value); ?>" <?php if(in_array($value, (array) $diasAtencion, true)): echo 'checked'; endif; ?>> <?php echo e($label); ?></label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
        <label class="backend-field">Próximo espacio visible<input type="text" name="proximo_espacio" value="<?php echo e(old('proximo_espacio',$profile?->proximo_espacio)); ?>" placeholder="Ej. Mañana, 16:00 hrs"></label>
        <div class="backend-field full">
          <span>Horario de servicio por día</span>
          <small class="muted-copy">El sistema validará que pacientes y profesionales solo puedan agendar dentro de estos horarios.</small>
          <div class="availability-grid">
            <?php $__currentLoopData = ['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="availability-row"><strong><?php echo e($label); ?></strong><input type="time" name="disponibilidad[<?php echo e($value); ?>][inicio]" value="<?php echo e(old('disponibilidad.'.$value.'.inicio', data_get($profile?->disponibilidad, $value.'.inicio', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.0') : null))); ?>"><span>a</span><input type="time" name="disponibilidad[<?php echo e($value); ?>][fin]" value="<?php echo e(old('disponibilidad.'.$value.'.fin', data_get($profile?->disponibilidad, $value.'.fin', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.1') : null))); ?>"></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>

      <div class="profile-form-section">
        <h3>Especialidades clínicas</h3>
        <div class="checkbox-row profile-checkboxes">
          <?php $__currentLoopData = ['TCC','Humanista','Psicoanálisis','Sistémica','Mindfulness','Neuropsicología']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label><input type="checkbox" name="enfoques[]" value="<?php echo e($value); ?>" <?php if(in_array($value, (array) $enfoques, true)): echo 'checked'; endif; ?>> <?php echo e($value); ?></label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="checkbox-row profile-checkboxes">
          <?php $__currentLoopData = ['Niños','Adolescentes','Adultos','Parejas','Familias','Adultos mayores']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label><input type="checkbox" name="poblaciones[]" value="<?php echo e($value); ?>" <?php if(in_array($value, (array) $poblaciones, true)): echo 'checked'; endif; ?>> <?php echo e($value); ?></label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="checkbox-row profile-checkboxes">
          <?php $__currentLoopData = ['Ansiedad','Depresión','Duelo','Estrés','Relaciones','Adicciones','Trauma','TDAH']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label><input type="checkbox" name="areas[]" value="<?php echo e($value); ?>" <?php if(in_array($value, (array) $areasSeleccionadas, true)): echo 'checked'; endif; ?>> <?php echo e($value); ?></label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>

      <div class="profile-form-section">
        <h3>Seguridad</h3>
        <div class="backend-form-grid two">
          <label class="backend-field">Nueva contraseña<input type="password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password"></label>
          <label class="backend-field">Confirmar nueva contraseña<input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" autocomplete="new-password"></label>
        </div>
      </div>

      <div class="profile-form-actions">
        <button class="btn-primary" type="submit">Guardar cambios</button>
      </div>
    </form>
  </section>

  <section class="profile-tab-panel" data-profile-panel="public">
    <article class="public-profile-preview-card">
      <header class="public-preview-hero">
        <div class="profile-modal-avatar"><?php echo e($initials ?: 'IR'); ?></div>
        <div>
          <h2><?php echo e($professionalPrefix); ?> <?php echo e($user->nombre_completo ?: $user->name); ?></h2>
          <p><?php echo e($profile?->especialidad_principal ?: 'Especialidad pendiente de completar'); ?></p>
          <?php if($user->professional_status === 'approved'): ?><span class="profile-status-chip ok">Perfil verificado por IRIS</span><?php else: ?><span class="profile-status-chip warn">Vista previa privada</span><?php endif; ?>
        </div>
      </header>
      <div class="specialist-modal-body-v2">
        <div class="specialist-modal-main">
          <section class="profile-detail-section"><h3>Acerca de mí</h3><p><?php echo e($profile?->biografia ?: 'Agrega una biografía profesional para que los pacientes conozcan tu enfoque.'); ?></p></section>
          <section class="profile-detail-section"><h3>Formación académica</h3><ul class="profile-education-list"><?php $__empty_1 = true; $__currentLoopData = $formacion; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><li><?php echo e($item); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><li>Formación pendiente de registrar.</li><?php endif; ?></ul></section>
          <section class="profile-detail-section"><h3>Especialidades</h3><div class="profile-specialty-tags"><?php $__empty_1 = true; $__currentLoopData = $especialidades->merge($areas)->unique()->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><span><?php echo e($item); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><span>Especialidades pendientes</span><?php endif; ?></div></section>
          <div class="profile-extra-grid">
            <div><h4>Idiomas</h4><p><?php echo e($profile?->idiomas ?: 'No especificado'); ?></p></div>
            <div><h4>Modalidad</h4><p><?php echo e($profile?->modalidad ?: 'No especificada'); ?></p></div>
            <div><h4>Ubicación</h4><p><?php echo e($profile?->ubicacion ?: 'No especificada'); ?></p></div>
            <div><h4>Duración</h4><p><?php echo e($profile?->duracion_sesion ?: 50); ?> minutos</p></div>
          </div>
        </div>
        <aside class="profile-booking-card">
          <h3>Información de consulta</h3>
          <div class="profile-price-row"><span>Tarifa por sesión:</span><strong><?php echo e($tarifa); ?></strong></div>
          <div class="profile-divider"></div>
          <h4>Días de atención</h4>
          <div class="profile-days-row">
            <?php $__currentLoopData = $diasMap; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="profile-day <?php echo e(in_array($key,(array)$diasAtencion,true) ? 'active' : ''); ?>"><?php echo e($label); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <div class="next-slot-box"><span>Próximo espacio:</span><strong><?php echo e($profile?->proximo_espacio ?: 'Pendiente de confirmar'); ?></strong></div>
          <button type="button" class="btn-primary w-100" disabled>Agendar cita ahora</button>
        </aside>
      </div>
    </article>
  </section>

  <section class="profile-tab-panel" data-profile-panel="billing">
    <section class="billing-hero-card dash-card">
      <span class="section-kicker">Suscripción profesional</span>
      <h2>Plan y pagos del <?php echo e($user->rol === 'psiquiatra' ? 'psiquiatra' : 'psicólogo'); ?></h2>
      <p>Administra el acceso a las herramientas clínicas después de la aprobación administrativa.</p>
      <span class="profile-status-chip <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
    </section>

    <div class="billing-layout-grid">
      <article class="dash-card plan-card-v2">
        <span class="section-kicker">Plan actual</span>
        <h3><?php echo e($subscription?->status === 'active' ? $subscription->plan : 'Sin suscripción activa'); ?></h3>
        <div class="plan-price-box">
          <div><strong>$800 MXN</strong><span>/ mes</span></div>
          <p>O plan anual $7,680 MXN</p>
        </div>
        <ul class="plan-feature-list">
          <li>✓ Gestión integral de pacientes y agenda</li>
          <li>✓ Perfil público visible en Buscar especialista</li>
          <li>✓ Solicitudes de cita y reagenda</li>
          <li>✓ Prescripción de tareas y seguimiento clínico</li>
          <li>✓ Auditoría clínica y adjuntos privados</li>
        </ul>
        <?php if($user->professional_status !== 'approved'): ?>
          <div class="backend-alert warning">Primero completa tu perfil y espera la aprobación del administrador. Después podrás pagar la suscripción con PayPal.</div>
        <?php elseif($subscription?->status === 'active'): ?>
          <div class="backend-alert success">Suscripción activa hasta <?php echo e($subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : 'fecha no especificada'); ?>.</div>
          <div class="backend-actions"><a class="btn-outline" href="<?php echo e(route('profesional.pago-suscripcion', ['cycle'=>'monthly'])); ?>">Actualizar método o renovar</a></div>
        <?php else: ?>
          <div class="backend-actions billing-actions">
            <a class="btn-primary" href="<?php echo e(route('profesional.pago-suscripcion', ['cycle'=>'monthly'])); ?>">Pagar mensual con PayPal</a>
            <a class="btn-outline" href="<?php echo e(route('profesional.pago-suscripcion', ['cycle'=>'annual'])); ?>">Pagar anual con PayPal</a>
          </div>
        <?php endif; ?>
      </article>

      <article class="dash-card payment-history-card">
        <span class="section-kicker">Facturación</span>
        <h3>Método de pago</h3>
        <div class="payment-method-placeholder">
          <strong>PayPal</strong>
          <p>El sistema registra el pago solo cuando PayPal confirma la captura.</p>
        </div>
        <h3>Historial de facturación</h3>
        <?php if($subscription): ?>
          <div class="invoice-row"><div><strong><?php echo e($subscription->starts_at?->format('d M Y') ?? 'Fecha no disponible'); ?></strong><p><?php echo e($subscription->plan); ?> · <?php echo e($subscription->status); ?></p></div><strong>$<?php echo e(number_format((float)$subscription->amount,2)); ?> MXN</strong></div>
        <?php else: ?>
          <div class="backend-empty">Aún no hay pagos de suscripción registrados.</div>
        <?php endif; ?>
        <?php if($user->professional_rejection_reason): ?><div class="backend-alert error"><strong>Motivo de rechazo:</strong> <?php echo e($user->professional_rejection_reason); ?></div><?php endif; ?>
      </article>
    </div>
  </section>
</main>
<script>
  document.querySelectorAll('[data-profile-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.profileTab;
      document.querySelectorAll('[data-profile-tab]').forEach((item) => item.classList.toggle('active', item === btn));
      document.querySelectorAll('[data-profile-panel]').forEach((panel) => panel.classList.toggle('active', panel.dataset.profilePanel === tab));
    });
  });
</script>
</body>
</html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/psicologo/perfil-psicologo.blade.php ENDPATH**/ ?>