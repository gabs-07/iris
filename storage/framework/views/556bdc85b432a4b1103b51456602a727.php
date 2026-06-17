<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi perfil | IRIS</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/perfil.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/iris-backend.css')); ?>">
</head>
<body class="dashboard-body">
<div id="menu-dinamico"></div>
<script src="<?php echo e(asset('js/menu-paciente.js')); ?>"></script>
<main class="dashboard-main patient-profile-page">
  <?php echo $__env->make('partials.paciente-header', ['title'=>'Mi perfil', 'subtitle'=>'Completa o actualiza tus datos personales, clínicos básicos y contacto de emergencia cuando lo necesites.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('partials.frontend-alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php if(! $user->profile_completed): ?>
    <div class="backend-alert warning"><strong>Perfil pendiente.</strong> Puedes usar IRIS y completar esta información desde aquí cuando tengas los datos listos.</div>
  <?php endif; ?>
  <?php
    $generos = [''=>'Seleccionar…','femenino'=>'Femenino','masculino'=>'Masculino','no-binario'=>'No binario','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
    $relacionesEmergencia = ['madre'=>'Madre','padre'=>'Padre','hermano/a'=>'Hermano/a','pareja'=>'Pareja','amigo/a'=>'Amigo/a','familiar'=>'Familiar','tutor/a'=>'Tutor/a','otro'=>'Otro'];
    $estadosCiviles = [''=>'Seleccionar…','soltero/a'=>'Soltero/a','casado/a'=>'Casado/a','union-libre'=>'Unión libre','separado/a'=>'Separado/a','divorciado/a'=>'Divorciado/a','viudo/a'=>'Viudo/a','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
  ?>
  <form method="POST" action="<?php echo e(route('paciente.perfil.update')); ?>" class="dash-card backend-form-grid patient-profile-form-v2">
    <?php echo csrf_field(); ?>
    <div class="card-top"><div><h2>Datos personales</h2><p class="card-subtitle">Información de tu cuenta y datos necesarios para la atención.</p></div><button class="btn-primary" type="submit">Guardar perfil</button></div>
    <div class="backend-form-grid two">
      <label class="backend-field">Nombre(s)<input type="text" name="nombre" value="<?php echo e(old('nombre',$user->nombre)); ?>" placeholder="Ej. Israel" autocomplete="given-name"></label>
      <label class="backend-field">Apellidos<input type="text" name="apellidos" value="<?php echo e(old('apellidos',$user->apellidos)); ?>" placeholder="Ej. Márquez Cárdenas" autocomplete="family-name"></label>
      <label class="backend-field">Teléfono celular<input type="tel" name="telefono" value="<?php echo e(old('telefono',$user->telefono)); ?>" placeholder="55 1234 5678" autocomplete="tel" inputmode="tel"></label>
      <label class="backend-field">Género<select name="genero">
        <?php $__currentLoopData = $generos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($value); ?>" <?php if(old('genero',$user->genero)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select></label>
      <label class="backend-field">Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="<?php echo e(old('fecha_nacimiento', optional($user->fecha_nacimiento)->format('Y-m-d'))); ?>"></label>
      <label class="backend-field">Ocupación<input type="text" name="ocupacion" value="<?php echo e(old('ocupacion',$user->patientProfile?->ocupacion)); ?>" placeholder="Ej. Estudiante, ingeniero/a, docente"></label>
    </div>

    <h2>Contacto de emergencia</h2>
    <div class="backend-form-grid three">
      <label class="backend-field">Nombre completo<input type="text" name="emergencia_nombre" value="<?php echo e(old('emergencia_nombre',$user->emergencyContact?->nombre)); ?>" placeholder="Ej. María Cárdenas"></label>
      <label class="backend-field">Relación<select name="emergencia_relacion">
        <option value="">Seleccionar…</option>
        <?php $__currentLoopData = $relacionesEmergencia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($value); ?>" <?php if(old('emergencia_relacion',$user->emergencyContact?->relacion)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select></label>
      <label class="backend-field">Teléfono<input type="tel" name="emergencia_telefono" value="<?php echo e(old('emergencia_telefono',$user->emergencyContact?->telefono)); ?>" placeholder="55 1234 5678" inputmode="tel"></label>
    </div>

    <h2>Información clínica inicial</h2>
    <div class="backend-form-grid two">
      <label class="backend-field">¿Has tomado terapia previamente?<select name="terapia_previa">
        <?php $__currentLoopData = [''=>'Seleccionar…','no'=>'No','si'=>'Sí','actualmente'=>'Actualmente en terapia']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($value); ?>" <?php if(old('terapia_previa',$user->patientProfile?->terapia_previa)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select></label>
      <label class="backend-field">Medicación actual<select name="medicacion_actual">
        <?php $__currentLoopData = [''=>'Seleccionar…','no'=>'No','si'=>'Sí','prefiero-comentarlo'=>'Prefiero comentarlo en sesión']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($value); ?>" <?php if(old('medicacion_actual',$user->patientProfile?->medicacion_actual)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select></label>
      <label class="backend-field">Estado civil<select name="estado_civil">
        <?php $__currentLoopData = $estadosCiviles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($value); ?>" <?php if(old('estado_civil',$user->patientProfile?->estado_civil)===$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select></label>
      <label class="backend-field full">Motivo principal de consulta<textarea name="motivo_consulta" rows="4" placeholder="Ej. Me gustaría trabajar ansiedad, estrés o cambios recientes en mi vida."><?php echo e(old('motivo_consulta',$user->patientProfile?->motivo_consulta)); ?></textarea></label>
      <label class="backend-field full">Objetivos terapéuticos<textarea name="objetivos" rows="4" placeholder="Ej. Aprender herramientas para regular emociones y mejorar mi descanso."><?php echo e(old('objetivos',$user->patientProfile?->objetivos)); ?></textarea></label>
      <label class="backend-field full">Antecedentes importantes<textarea name="antecedentes" rows="4" placeholder="Ej. Terapias previas, diagnósticos anteriores o eventos relevantes que quieras compartir."><?php echo e(old('antecedentes',$user->patientProfile?->antecedentes)); ?></textarea></label>
      <label class="backend-field full">Alergias o restricciones médicas<textarea name="alergias" rows="3" placeholder="Ej. Alergias conocidas, restricciones médicas o medicamentos que no toleras."><?php echo e(old('alergias',$user->patientProfile?->alergias)); ?></textarea></label>
      <label class="backend-field full">Domicilio o referencia general<textarea name="domicilio" rows="3" placeholder="Ej. Alcaldía o municipio, ciudad y estado. No es necesario capturar calle completa si no deseas."><?php echo e(old('domicilio',$user->patientProfile?->domicilio)); ?></textarea></label>
    </div>

    <h2>Seguridad</h2>
    <div class="backend-form-grid two">
      <label class="backend-field">Nueva contraseña<input type="password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password"></label>
      <label class="backend-field">Confirmar nueva contraseña<input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" autocomplete="new-password"></label>
    </div>
    <div class="profile-form-actions"><button class="btn-primary" type="submit">Guardar perfil</button></div>
  </form>
</main>
</body>
</html>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/paciente/perfil-paciente.blade.php ENDPATH**/ ?>