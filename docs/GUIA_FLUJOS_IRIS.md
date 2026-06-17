# Guía de flujos principales - IRIS

## Autenticación
- Registro: `resources/views/registro.blade.php` y `app/Http/Controllers/Auth/RegisteredUserController.php`.
- Login: `resources/views/login.blade.php`.
- El registro guarda usuario, contacto de emergencia, consentimiento legal y perfil base.

## Paciente
- Dashboard: `PacienteController@dashboard` y `resources/views/paciente/dashboard-paciente.blade.php`.
- Buscar especialista: `PacienteController@buscarEspecialista`.
- Agendar cita: `PacienteController@agendarCita` y `storeAgendarCita`.
- Pago de cita: `PacienteController@pagoCita`, PayPal controllers y `resources/views/paciente/pago-cita.blade.php`.
- Gestión de citas: `PacienteController@gestionCitas`.
- Sesión/videollamada: `PacienteController@sesion` y `resources/views/paciente/sesion.blade.php`.
- Diario: `PacienteController@diario`, `storeDiario` y vistas `diario-paciente`/`diario-todas`.
- Tareas: `PacienteController@misTareas`, `completeTask`, `unsubmitTask`, `viewTaskPdf`.

## Profesional
- Dashboard: `ProfesionalController@dashboard`.
- Agenda: `ProfesionalController@agenda` y `storeAgenda`.
- Solicitudes: `ProfesionalController@solicitudes` y `updateSolicitud`.
- Pacientes/notas/tareas: `ProfesionalController@pacientes`, `storePacienteData`, `reviewTask`, `viewTaskPdf`, `viewClinicalAttachment`.
- Prescripciones: `ProfesionalController@prescripciones`, `storePrescripcion`, `destroyPrescripcion`.
- Sesiones: `ProfesionalController@sesion` y `storeSesion`.

## Administración
- Dashboard y usuarios: `AdminController@dashboard`, `usuarios`, `storeUsuario`.
- Aprobación profesional: `profesionales`, `showProfesional`, `approveProfesional`, `rejectProfesional`.
- Comunidad: `communityReports`, `resolveCommunityReport`.

## Zoom
- Cliente API: `app/Services/ZoomClient.php`.
- Creación de videollamadas: `app/Services/AppointmentVideoService.php`.
- Ventana de sesión y citas perdidas: `app/Services/AppointmentLifecycleService.php`.
- Campos: migraciones `2026_06_08_000500_add_zoom_fields_to_appointments.php` y `2026_06_08_000600_add_missed_status_to_appointments.php`.

## Legal
- Índice legal: `/legal`, vista `resources/views/legal/index.blade.php`.
- Documentos: `resources/views/legal/view/*.blade.php`.
- CSS legal: `public/legal/css/legal.css` y copia compatible `public/css/legal.css`.
- El registro, login, perfil, agenda y comunidad enlazan los documentos correspondientes.

## Flujo de Auxilio con Zoom y Modo Escucha

1. El botón flotante de Auxilio solo se renderiza para usuarios con rol `paciente` desde `resources/views/partials/floating-auxilio.blade.php`.
2. El profesional activa o desactiva su disponibilidad desde el interruptor **Modo Escucha** del dashboard profesional. El estado se guarda en `professional_profiles.modo_escucha_activo` mediante la ruta `POST /psicologo/modo-escucha`.
3. Cuando el paciente entra a `paciente/auxilio-paciente` y presiona **Buscar psicólogo en Modo Escucha**, el controlador `PacienteController::solicitarAuxilioZoom()` llama a `AuxilioSessionService`.
4. `AuxilioSessionService` busca profesionales aprobados, con perfil completo, suscripción activa y `modo_escucha_activo = 1`.
5. Si encuentra un profesional disponible, crea una cita inmediata con `requested_by = auxilio`, `status = accepted`, `payment_status = waived`, `modality = Videollamada` y `amount = 0`.
6. Después se llama a `AppointmentVideoService`, que usa `ZoomClient` para crear la reunión en Zoom y guardar `zoom_meeting_id`, `zoom_join_url`, `zoom_start_url`, `zoom_password` y `room_link`.
7. El paciente ve el botón **Entrar a sesión de auxilio por Zoom** en la vista de Auxilio.
8. El profesional ve la solicitud activa en su dashboard con el botón **Entrar a Zoom**.
9. La sesión de auxilio usa la misma ventana de disponibilidad general: se mantiene activa durante 1 hora desde su creación.
