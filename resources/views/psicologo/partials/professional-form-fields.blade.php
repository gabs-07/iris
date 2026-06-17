@php
  $profile = $user->professionalProfile;
  $generos = [''=>'Seleccionar…','femenino'=>'Femenino','masculino'=>'Masculino','no-binario'=>'No binario','prefiero-no-decir'=>'Prefiero no decirlo','otro'=>'Otro'];
  $relacionesEmergencia = ['madre'=>'Madre','padre'=>'Padre','hermano/a'=>'Hermano/a','pareja'=>'Pareja','amigo/a'=>'Amigo/a','familiar'=>'Familiar','tutor/a'=>'Tutor/a','otro'=>'Otro'];
@endphp
<label>Nombre(s)<input type="text" name="nombre" value="{{ old('nombre',$user->nombre) }}" placeholder="Ej. Sofía" autocomplete="given-name"></label>
<label>Apellidos<input type="text" name="apellidos" value="{{ old('apellidos',$user->apellidos) }}" placeholder="Ej. Hernández López" autocomplete="family-name"></label>
<label>Teléfono celular<input type="tel" name="telefono" value="{{ old('telefono',$user->telefono) }}" placeholder="55 1234 5678" autocomplete="tel" inputmode="tel"></label>
<label>Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', optional($user->fecha_nacimiento)->format('Y-m-d')) }}"></label>
<label>Género<select name="genero">@foreach($generos as $value => $label)<option value="{{ $value }}" @selected(old('genero',$user->genero)===$value)>{{ $label }}</option>@endforeach</select></label>
<label>Contacto de emergencia<input type="text" name="emergencia_nombre" value="{{ old('emergencia_nombre',$user->emergencyContact?->nombre) }}" placeholder="Ej. Laura Hernández"></label>
<label>Relación con contacto<select name="emergencia_relacion"><option value="">Seleccionar…</option>@foreach($relacionesEmergencia as $value => $label)<option value="{{ $value }}" @selected(old('emergencia_relacion',$user->emergencyContact?->relacion)===$value)>{{ $label }}</option>@endforeach</select></label>
<label>Teléfono de emergencia<input type="tel" name="emergencia_telefono" value="{{ old('emergencia_telefono',$user->emergencyContact?->telefono) }}" placeholder="55 1234 5678" inputmode="tel"></label>
<label>Título profesional<input type="text" name="titulo_profesional" value="{{ old('titulo_profesional',$profile?->titulo_profesional) }}" placeholder="Ej. Licenciatura en Psicología"></label>
<label>Especialidad principal<input type="text" name="especialidad_principal" required value="{{ old('especialidad_principal',$profile?->especialidad_principal) }}" placeholder="Ej. Psicología clínica y manejo emocional"></label>
<label>Cédula profesional<input type="text" name="cedula_profesional" required value="{{ old('cedula_profesional',$profile?->cedula_profesional) }}" placeholder="Ej. 12345678" inputmode="numeric"></label>
<label>Cédula especialidad<input type="text" name="cedula_especialidad" value="{{ old('cedula_especialidad',$profile?->cedula_especialidad) }}" placeholder="Ej. 87654321" inputmode="numeric"></label>
<label>Institución<input type="text" name="institucion" value="{{ old('institucion',$profile?->institucion) }}" placeholder="Ej. Universidad Nacional Autónoma de México"></label>
<label>Posgrado<input type="text" name="posgrado" value="{{ old('posgrado',$profile?->posgrado) }}" placeholder="Ej. Maestría en terapia cognitivo-conductual"></label>
<label>Años de experiencia<input type="number" min="0" max="80" name="experiencia_anios" value="{{ old('experiencia_anios',$profile?->experiencia_anios) }}" placeholder="Ej. 6"></label>
<label>Modalidad<select name="modalidad"><option value="ambas" @selected(old('modalidad',$profile?->modalidad)==='ambas')>Presencial y en línea</option><option value="online" @selected(old('modalidad',$profile?->modalidad)==='online')>En línea</option><option value="videollamada" @selected(old('modalidad',$profile?->modalidad)==='videollamada')>Videollamada</option><option value="presencial" @selected(old('modalidad',$profile?->modalidad)==='presencial')>Presencial</option><option value="hibrida" @selected(old('modalidad',$profile?->modalidad)==='hibrida')>Híbrida</option></select></label>
<label>Ubicación<input type="text" name="ubicacion" value="{{ old('ubicacion',$profile?->ubicacion) }}" placeholder="Ej. Ciudad de México o atención en línea"></label>
<label>Idiomas<input type="text" name="idiomas" value="{{ old('idiomas',$profile?->idiomas) }}" placeholder="Ej. Español, inglés"></label>
<label>Biografía<textarea name="biografia" rows="4" placeholder="Describe tu enfoque, experiencia y estilo de acompañamiento.">{{ old('biografia',$profile?->biografia) }}</textarea></label>
<label>Servicios<textarea name="servicios" rows="3" placeholder="Ej. Terapia individual, orientación inicial, seguimiento terapéutico.">{{ old('servicios',$profile?->servicios) }}</textarea></label>
<label>Presentación pública<textarea name="presentacion" rows="3" placeholder="Ej. Mensaje breve para explicar cómo trabajas con tus pacientes.">{{ old('presentacion',$profile?->presentacion) }}</textarea></label>
<label>Tarifa por sesión<input type="number" min="0" step="50" name="costo_min" value="{{ old('costo_min',$profile?->costo_min) }}" placeholder="Ej. 800"></label>
<label>Tarifa máxima opcional<input type="number" min="0" step="50" name="costo_max" value="{{ old('costo_max',$profile?->costo_max) }}" placeholder="Ej. 1200"></label>
<label>Duración de sesión<input type="number" min="30" max="180" step="5" name="duracion_sesion" value="{{ old('duracion_sesion',$profile?->duracion_sesion ?? 50) }}" placeholder="Ej. 50"></label>
@php($diasAtencion = old('dias_atencion', $profile?->dias_atencion ?? []))
<div class="backend-field full">
  <span>Horario de servicio por día</span>
  <div class="availability-grid">
    @foreach(['lunes'=>'Lunes','martes'=>'Martes','miércoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sábado'=>'Sábado','domingo'=>'Domingo'] as $value => $label)
      <div class="availability-row"><strong>{{ $label }}</strong><input type="time" name="disponibilidad[{{ $value }}][inicio]" value="{{ old('disponibilidad.'.$value.'.inicio', data_get($profile?->disponibilidad, $value.'.inicio', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.0') : null)) }}"><span>a</span><input type="time" name="disponibilidad[{{ $value }}][fin]" value="{{ old('disponibilidad.'.$value.'.fin', data_get($profile?->disponibilidad, $value.'.fin', is_array(data_get($profile?->disponibilidad, $value)) ? data_get($profile?->disponibilidad, $value.'.1') : null)) }}"></div>
    @endforeach
  </div>
</div>
