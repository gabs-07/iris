# Reporte QA profesional IRIS — Validación integral y correcciones

Fecha: 2026-06-12  
Proyecto validado: IRIS Laravel/Blade

## 1. Alcance de la auditoría QA

Se revisó el sistema como si fuera una demo evaluada por reglas de negocio, no solo por presencia visual de pantallas. La validación se centró en:

- Registro, login y perfiles por rol.
- Formularios de paciente, profesional, admin, citas, tareas, diario y chat profesional.
- Reglas de negocio de agenda, horarios de servicio, modalidad, pagos y estados de cita.
- Permisos por rol y relaciones paciente-profesional.
- Auxilio para paciente e invitado.
- Diario emocional agrupado por día y autorización al profesional.
- CSS servido desde `public`.
- Logs HTTP importantes.
- Sintaxis PHP/Blade.

## 2. Hallazgos importantes y correcciones aplicadas

### QA-001 — Agendado fuera del horario real del profesional

**Hallazgo:**  
El perfil profesional permitía capturar días de atención y una disponibilidad general, pero al agendar una cita el backend no impedía seleccionar horarios fuera del servicio del profesional. Ejemplo: si el psicólogo atiende de 08:00 a 15:00, el paciente podía intentar agendar a las 20:00.

**Corrección:**  
Se creó `app/Services/AppointmentBusinessRules.php` con validación centralizada para citas. Ahora el sistema valida:

- Día de atención permitido.
- Rango de hora por día.
- Duración de sesión dentro del rango.
- Modalidad compatible con el perfil.
- Profesional aprobado y con suscripción activa.
- Cruce de horarios del paciente y del profesional.

**Dónde se aplica:**

- `PacienteController@storeAgendarCita`
- `PacienteController@storeGestionCitas`
- `PacienteController@solicitarReagenda`
- `PacienteController@aceptarReagenda`
- `PacienteController@aceptarSolicitudProfesional`
- `ProfesionalController@storeAgenda`
- `ProfesionalController@updateSolicitud`

---

### QA-002 — Falta de horario estructurado por día

**Hallazgo:**  
El formulario profesional solo tenía texto libre para disponibilidad. Eso era difícil de validar porque un texto como “lunes a jueves de 8 a 3” no es confiable para bloquear horarios.

**Corrección:**  
Se añadieron campos de hora por día:

- Lunes: inicio / fin
- Martes: inicio / fin
- Miércoles: inicio / fin
- Jueves: inicio / fin
- Viernes: inicio / fin
- Sábado: inicio / fin
- Domingo: inicio / fin

**Vistas corregidas:**

- `resources/views/perfil/completar-profesional.blade.php`
- `resources/views/psicologo/perfil-psicologo.blade.php`
- `resources/views/psicologo/partials/professional-form-fields.blade.php`

---

### QA-003 — Solapamiento de citas

**Hallazgo:**  
El sistema permitía crear dos citas en el mismo horario para el mismo profesional o para el mismo paciente.

**Corrección:**  
Se agregó validación de cruce de horario usando `starts_at` y `ends_at`. Se bloquean citas activas con estados:

- `pending_payment`
- `pending`
- `accepted`
- `rescheduled`

**Resultado esperado:**  
Si un paciente o profesional ya tiene una cita de 10:00 a 10:50, no puede crearse otra cita que cruce ese rango.

---

### QA-004 — Modalidad incompatible con el profesional

**Hallazgo:**  
Un profesional marcado como solo presencial podía recibir cita por videollamada, o uno solo en línea podía recibir solicitud presencial.

**Corrección:**  
Se agregó validación de modalidad:

- Perfil `presencial`: rechaza videollamada/llamada/online.
- Perfil `online` o `videollamada`: rechaza presencial.
- Perfil `ambas` o `hibrida`: acepta ambas.

---

### QA-005 — Reagenda fuera de horario o con cruce

**Hallazgo:**  
Las propuestas de reagenda podían enviarse o aceptarse sin validar el horario real del profesional ni el cruce con otras citas.

**Corrección:**  
Se validan las reglas de agenda también cuando:

- El paciente solicita reagenda.
- El profesional propone reagenda.
- El paciente acepta una propuesta.
- El profesional acepta una solicitud con fecha/hora existente.

---

### QA-006 — Costo máximo menor que costo mínimo

**Hallazgo:**  
El perfil permitía una tarifa máxima menor que la tarifa base.

**Corrección:**  
Se agregó `gte:costo_min` en validaciones de perfil profesional.

---

### QA-007 — Días de atención inválidos

**Hallazgo:**  
El backend aceptaba cualquier cadena en `dias_atencion[]`.

**Corrección:**  
Ahora solo acepta:

- lunes
- martes
- miércoles
- jueves
- viernes
- sábado
- domingo

---

### QA-008 — Fechas de nacimiento futuras en edición profesional

**Hallazgo:**  
Al editar perfil profesional se podía guardar una fecha de nacimiento futura.

**Corrección:**  
Se agregó `before:today`.

---

### QA-009 — Tareas con fecha vencida desde creación

**Hallazgo:**  
El profesional podía asignar tareas con fecha anterior al día actual.

**Corrección:**  
Se agregó `after_or_equal:today` en fechas de tareas.

---

### QA-010 — Falta de ayuda visual al paciente al agendar

**Hallazgo:**  
Aunque el backend ya valida, en demo conviene que el usuario vea el rango permitido antes de enviar.

**Corrección:**  
Se agregó ayuda visual en `agendar-cita.blade.php`. Al seleccionar fecha, muestra:

- si el profesional atiende ese día;
- horario permitido de inicio y fin;
- límites en el input de hora cuando existe disponibilidad estructurada.

---

### QA-011 — CSS de nuevos componentes

**Hallazgo:**  
Los nuevos campos de disponibilidad necesitaban estilos consistentes y servidos desde `public`.

**Corrección:**  
Se agregaron clases responsivas en:

- `public/css/global.css`

---

### QA-012 — Seeders con disponibilidad heredada

**Hallazgo:**  
El seeder guardaba disponibilidad como arreglo simple `['09:00', '12:00']`.

**Corrección:**  
Se actualizó a estructura clara:

```php
'lunes' => ['inicio' => '09:00', 'fin' => '12:00']
```

El validador también conserva compatibilidad con la estructura antigua para no romper datos existentes.

## 3. Validaciones técnicas ejecutadas

### Sintaxis PHP/Blade

Se ejecutó `php -l` sobre archivos en:

- `app/`
- `database/`
- `routes/`
- `resources/views/`

**Resultado:** sin errores de sintaxis.

### Formularios POST sin CSRF

Se revisaron formularios `POST` en Blade.

**Resultado:** no se encontraron formularios POST sin `@csrf`.

### CSS referenciado desde `public`

Se revisaron referencias `asset('css/...')`.

**Resultado:** no se encontraron rutas CSS faltantes en `public/css`.

## 4. Casos de prueba recomendados para la demo

### Registro y acceso

1. Registrar paciente con datos completos y consentimientos marcados.
   - Esperado: crea cuenta, contacto de emergencia, consentimiento legal y solicita verificación.

2. Registrar profesional sin aceptar condiciones profesionales.
   - Esperado: rechaza registro.

3. Registrar con correo duplicado.
   - Esperado: rechaza por correo ya registrado.

4. Registrar con fecha de nacimiento futura.
   - Esperado: rechaza fecha.

### Perfil profesional

5. Guardar psicólogo con días lunes a jueves y horario lunes 08:00-15:00.
   - Esperado: guarda disponibilidad estructurada.

6. Guardar tarifa mínima 800 y máxima 500.
   - Esperado: rechaza porque la máxima no puede ser menor.

7. Guardar horario 15:00-08:00.
   - Esperado: rechaza porque la hora final debe ser mayor.

8. Intentar manipular `dias_atencion[]` con valor `feriado`.
   - Esperado: rechaza valor inválido.

### Agenda del paciente

9. Agendar con psicólogo lunes 10:00 dentro de 08:00-15:00.
   - Esperado: permite crear solicitud y manda a pago.

10. Agendar lunes 16:00 cuando atiende 08:00-15:00.
    - Esperado: rechaza por fuera de horario.

11. Agendar domingo con profesional que solo atiende lunes-jueves.
    - Esperado: rechaza día no disponible.

12. Agendar modalidad presencial con profesional online.
    - Esperado: rechaza modalidad incompatible.

13. Agendar otra cita que se cruza con una activa.
    - Esperado: rechaza cruce de horario.

14. Agendar seguimiento con profesional sin relación clínica previa manipulando el formulario.
    - Esperado: rechaza porque no existe seguimiento.

### Agenda del profesional

15. Psicólogo agenda cita directa dentro de su horario.
    - Esperado: crea sesión aceptada y genera Zoom.

16. Psicólogo agenda cita directa fuera de su horario.
    - Esperado: rechaza.

17. Psicólogo agenda cita directa en horario donde ya tiene otra cita.
    - Esperado: rechaza por cruce.

18. Psicólogo propone reagenda fuera de su horario.
    - Esperado: rechaza.

19. Paciente acepta reagenda que ya pasó.
    - Esperado: rechaza.

20. Paciente acepta reagenda dentro de horario y sin cruce.
    - Esperado: acepta y genera/actualiza Zoom.

### Diario emocional

21. Paciente guarda nota a las 09:00 y otra a las 18:00 del mismo día.
    - Esperado: queda un solo registro del día con ambas horas.

22. Paciente guarda nota después de las 00:00.
    - Esperado: crea nuevo registro del nuevo día.

23. Paciente autoriza a un psicólogo.
    - Esperado: el profesional autorizado puede ver el diario.

24. Otro profesional no autorizado intenta verlo.
    - Esperado: no aparece en su listado.

### Auxilio

25. Invitado entra a `/auxilio-invitado` sin sesión y acepta aviso.
    - Esperado: crea solicitud de auxilio y conecta con profesional en modo escucha.

26. Invitado finaliza auxilio.
    - Esperado: limpia sesión temporal y lo invita a registrarse.

27. Paciente usa botón de auxilio.
    - Esperado: crea sesión Zoom con profesional en modo escucha.

28. No hay profesional en modo escucha.
    - Esperado: muestra aviso controlado, sin error 500.

### Chat profesional

29. Psicólogo envía mensaje con tags permitidos.
    - Esperado: se guarda y se muestra.

30. Paciente intenta acceder al chat profesional.
    - Esperado: 403/redirect por middleware de rol.

31. Enviar más de 5 tags o tag no permitido.
    - Esperado: rechaza validación.

### Tareas

32. Profesional asigna tarea con fecha futura.
    - Esperado: se crea y notifica.

33. Profesional asigna tarea con fecha pasada.
    - Esperado: rechaza.

34. Paciente entrega tarea con PDF válido.
    - Esperado: guarda PDF y cambia a entregada.

35. Paciente entrega archivo que no es PDF.
    - Esperado: rechaza.

### Seguridad y rutas

36. Usuario no autenticado entra a ruta de paciente.
    - Esperado: redirige a login.

37. Paciente intenta entrar a admin.
    - Esperado: 403.

38. Psicólogo sin aprobación intenta entrar a herramientas profesionales.
    - Esperado: redirige/deniega hasta completar aprobación/suscripción.

39. Ruta inexistente.
    - Esperado: 404 registrado en logs HTTP.

40. Error 5xx generado por excepción controlada.
    - Esperado: queda registro por middleware de logs.

## 5. Observaciones finales de QA

La corrección más importante para la demo es la nueva capa `AppointmentBusinessRules`, porque evita inconsistencias visibles al evaluador: horarios fuera de servicio, citas duplicadas, modalidad incorrecta y reagendas inválidas.

Para la demostración, se recomienda preparar un psicólogo con:

- Días: lunes a viernes.
- Horario: 08:00 a 15:00.
- Duración: 50 minutos.
- Suscripción activa.
- Perfil aprobado.

Luego mostrar primero un caso correcto y después un caso bloqueado, por ejemplo intentar agendar a las 16:00. Eso demuestra que el sistema no solo tiene vistas, sino reglas de negocio reales.
