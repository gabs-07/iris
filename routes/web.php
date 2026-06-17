<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Billing\PayPalPaymentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\GuestAuxilioController;
use App\Http\Controllers\ProfessionalChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Paciente\PacienteController;
use App\Http\Controllers\Profesional\ProfesionalController;
use App\Http\Controllers\Profile\ProfileCompletionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::view('/index', 'index')->name('index');
Route::redirect('/index.html', '/');

Route::get('/auxilio-invitado', [GuestAuxilioController::class, 'show'])->name('guest.auxilio');
Route::post('/auxilio-invitado', [GuestAuxilioController::class, 'request'])->name('guest.auxilio.request');
Route::post('/auxilio-invitado/finalizar', [GuestAuxilioController::class, 'finish'])->name('guest.auxilio.finish');
Route::redirect('/auxilio', '/auxilio-invitado')->name('auxilio.public');


require __DIR__.'/auth.php';

Route::get('/dashboard', function () {
    $user = auth()->user();
    if (! $user) return redirect('/login');
    if (! $user->hasVerifiedEmail()) return redirect()->route('verification.notice');
    return match ($user->rol) {
        'admin' => redirect('/admin'),
        'invitado' => redirect('/auxilio-invitado'),
        'paciente' => redirect('/paciente/dashboard-paciente'),
        'psicologo', 'psiquiatra' => redirect($user->professional_status === 'approved' ? '/psicologo/dashboard-psicologo' : '/psicologo/perfil-psicologo'),
        default => redirect('/'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificaciones/{id}', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markReadBack'])->name('notifications.mark');
    Route::post('/notificaciones/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'verified'])->prefix('perfil')->name('perfil.')->group(function () {
    Route::get('/completar', [ProfileCompletionController::class, 'show'])->name('completar.show');
    Route::post('/completar', [ProfileCompletionController::class, 'store'])->name('completar.store');
});
Route::middleware(['auth', 'verified'])->get('/completar-perfil', [ProfileCompletionController::class, 'show'])->name('perfil.completar.direct');
Route::middleware(['auth', 'verified'])->post('/completar-perfil', [ProfileCompletionController::class, 'store'])->name('perfil.completar.direct.store');
Route::redirect('/completar-datos', '/completar-perfil')->middleware(['auth', 'verified']);

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/profesionales', [AdminController::class, 'profesionales'])->name('profesionales.index');
    Route::get('/profesionales/{user}', [AdminController::class, 'showProfesional'])->name('profesionales.show');
    Route::post('/profesionales/{user}/aprobar', [AdminController::class, 'approveProfesional'])->name('profesionales.approve');
    Route::post('/profesionales/{user}/rechazar', [AdminController::class, 'rejectProfesional'])->name('profesionales.reject');
    Route::get('/usuarios', [AdminController::class, 'usuarios'])->name('usuarios.index');
    Route::post('/usuarios', [AdminController::class, 'storeUsuario'])->name('usuarios.store');
    Route::get('/comunidad/reportes', [AdminController::class, 'communityReports'])->name('comunidad.reportes');
    Route::post('/comunidad/reportes/{report}', [AdminController::class, 'resolveCommunityReport'])->name('comunidad.reportes.resolve');
});

Route::middleware(['auth', 'role:paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard-paciente', [PacienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/buscar-especialista', [PacienteController::class, 'buscarEspecialista'])->name('buscar-especialista');
    Route::get('/agendar-cita', [PacienteController::class, 'agendarCita'])->name('agendar-cita');
    Route::post('/agendar-cita', [PacienteController::class, 'storeAgendarCita'])->name('agendar-cita.store');
    Route::get('/gestion-citas', [PacienteController::class, 'gestionCitas'])->name('gestion-citas');
    Route::post('/gestion-citas', [PacienteController::class, 'storeGestionCitas'])->name('gestion-citas.store');
    Route::post('/gestion-citas/{appointment}/aceptar-reagenda', [PacienteController::class, 'aceptarReagenda'])->name('gestion-citas.aceptar-reagenda');
    Route::post('/gestion-citas/{appointment}/aceptar-solicitud', [PacienteController::class, 'aceptarSolicitudProfesional'])->name('gestion-citas.aceptar-solicitud');
    Route::post('/gestion-citas/{appointment}/reagendar', [PacienteController::class, 'solicitarReagenda'])->name('gestion-citas.reagendar');
    Route::post('/gestion-citas/{appointment}/cancelar', [PacienteController::class, 'cancelarCita'])->name('gestion-citas.cancelar');
    Route::get('/pago-cita', [PacienteController::class, 'pagoCita'])->name('pago-cita');
    Route::post('/pago-cita', [PacienteController::class, 'storePagoCita'])->name('pago-cita.store');
    Route::post('/paypal/citas/{appointment}/crear', [PayPalPaymentController::class, 'createAppointmentOrder'])->name('paypal.appointments.create');
    Route::post('/paypal/citas/{appointment}/capturar', [PayPalPaymentController::class, 'captureAppointmentOrder'])->name('paypal.appointments.capture');
    Route::get('/paypal/citas/{appointment}/capturar', [PayPalPaymentController::class, 'captureAppointmentOrder'])->name('paypal.appointments.capture.redirect');
    Route::get('/diario-paciente', [PacienteController::class, 'diario'])->name('diario');
    Route::get('/diario-paciente/todas', [PacienteController::class, 'diarioTodas'])->name('diario.todas');
    Route::post('/diario-paciente', [PacienteController::class, 'storeDiario'])->name('diario.store');
    Route::get('/mis-tareas', [PacienteController::class, 'misTareas'])->name('mis-tareas');
    Route::post('/mis-tareas/{task}/completar', [PacienteController::class, 'completeTask'])->name('mis-tareas.complete');
    Route::get('/mis-tareas/{task}/pdf', [PacienteController::class, 'viewTaskPdf'])->name('mis-tareas.pdf');
    Route::post('/mis-tareas/{task}/desentregar', [PacienteController::class, 'unsubmitTask'])->name('mis-tareas.unsubmit');
    Route::get('/perfil-paciente', [PacienteController::class, 'perfil'])->name('perfil');
    Route::post('/perfil-paciente', [PacienteController::class, 'updatePerfil'])->name('perfil.update');
    Route::get('/auxilio-paciente', [PacienteController::class, 'auxilio'])->name('auxilio');
    Route::post('/auxilio-paciente/zoom', [PacienteController::class, 'solicitarAuxilioZoom'])->name('auxilio.zoom');
    Route::get('/sesion', [PacienteController::class, 'sesion'])->name('sesion');
});

Route::middleware(['auth', 'role:psicologo,psiquiatra,doctor_interno'])->prefix('psicologo')->name('profesional.')->group(function () {
    Route::get('/perfil-psicologo-sinsub', [ProfesionalController::class, 'perfilSinSub'])->name('perfil.sinsub');
    Route::post('/perfil-psicologo-sinsub', [ProfesionalController::class, 'updatePerfil'])->name('perfil.submit');
    Route::get('/perfil-psicologo', [ProfesionalController::class, 'perfil'])->name('perfil');
    Route::post('/perfil-psicologo', [ProfesionalController::class, 'updatePerfil'])->name('perfil.update');
    Route::get('/pago-suscripcion', [ProfesionalController::class, 'pagoSuscripcion'])->name('pago-suscripcion');
    Route::post('/pago-suscripcion', [ProfesionalController::class, 'storePagoSuscripcion'])->name('pago-suscripcion.store');
    Route::post('/paypal/suscripcion/crear', [PayPalPaymentController::class, 'createSubscriptionOrder'])->name('paypal.subscriptions.create');
    Route::post('/paypal/suscripcion/capturar', [PayPalPaymentController::class, 'captureSubscriptionOrder'])->name('paypal.subscriptions.capture');
    Route::get('/paypal/suscripcion/capturar', [PayPalPaymentController::class, 'captureSubscriptionOrder'])->name('paypal.subscriptions.capture.redirect');
});

Route::middleware(['auth', 'role:psicologo,psiquiatra,doctor_interno', 'professional.ready'])->prefix('psicologo')->name('profesional.')->group(function () {
    Route::get('/dashboard-psicologo', [ProfesionalController::class, 'dashboard'])->name('dashboard');
    Route::post('/modo-escucha', [ProfesionalController::class, 'actualizarModoEscucha'])->name('modo-escucha.update');
    Route::get('/agenda-psicologo', [ProfesionalController::class, 'agenda'])->name('agenda');
    Route::post('/agenda-psicologo', [ProfesionalController::class, 'storeAgenda'])->name('agenda.store');
    Route::get('/gestion-citas', fn () => redirect('/psicologo/agenda-psicologo'))->name('gestion-citas');
    Route::get('/solicitudes-psicologo', [ProfesionalController::class, 'solicitudes'])->name('solicitudes');
    Route::post('/solicitudes/{appointment}', [ProfesionalController::class, 'updateSolicitud'])->name('solicitudes.update');
    Route::get('/pacientes-psicologo', [ProfesionalController::class, 'pacientes'])->name('pacientes');
    Route::get('/diarios-autorizados', [ProfesionalController::class, 'diariosAutorizados'])->name('diarios-autorizados');
    Route::get('/chat-profesional', [ProfessionalChatController::class, 'index'])->name('chat-profesional');
    Route::post('/chat-profesional', [ProfessionalChatController::class, 'store'])->name('chat-profesional.store');
    Route::post('/pacientes-psicologo', [ProfesionalController::class, 'storePacienteData'])->name('pacientes.store');
    Route::get('/pacientes-psicologo/{patient}/adjuntos/{index}', [ProfesionalController::class, 'viewClinicalAttachment'])->name('pacientes.adjuntos.view');
    Route::post('/tareas/{task}/revisar', [ProfesionalController::class, 'reviewTask'])->name('tareas.review');
    Route::get('/tareas/{task}/pdf', [ProfesionalController::class, 'viewTaskPdf'])->name('tareas.pdf');
    Route::get('/prescripciones', [ProfesionalController::class, 'prescripciones'])->name('prescripciones');
    Route::post('/prescripciones', [ProfesionalController::class, 'storePrescripcion'])->name('prescripciones.store');
    Route::delete('/prescripciones/{prescription}', [ProfesionalController::class, 'destroyPrescripcion'])->name('prescripciones.destroy');
    Route::get('/sesion', [ProfesionalController::class, 'sesion'])->name('sesion');
    Route::post('/sesion', [ProfesionalController::class, 'storeSesion'])->name('sesion.store');
});

Route::middleware(['auth', 'verified', 'community.ready'])->prefix('comunidad')->name('comunidad.')->group(function () {
    Route::get('/comunidad', [CommunityController::class, 'index'])->name('index');
    Route::post('/comunidad', [CommunityController::class, 'store'])->name('store');
    Route::post('/comunidad/{post}/comentarios', [CommunityController::class, 'comment'])->name('comment');
    Route::post('/comunidad/{post}/like', [CommunityController::class, 'like'])->name('like');
    Route::post('/comunidad/{post}/reportar', [CommunityController::class, 'report'])->name('report');
    Route::put('/comunidad/{post}', [CommunityController::class, 'update'])->name('update');
    Route::delete('/comunidad/{post}', [CommunityController::class, 'destroy'])->name('destroy');
    Route::delete('/comentarios/{comment}', [CommunityController::class, 'destroyComment'])->name('comment.destroy');
    Route::get('/mis-publicaciones', [CommunityController::class, 'myPosts'])->name('mis-publicaciones');
});

Route::get('/api/cedulas/verificar', function (Request $request) {
    $numero = trim((string) $request->query('numero'));
    $valid = $numero !== '' && preg_match('/^\d{6,8}$/', $numero) === 1;

    return response()->json([
        'ok' => $valid,
        'success' => true,
        'valid' => $valid,
        'numero' => $numero,
        'specialty' => null,
        'message' => $numero
            ? ($valid
                ? 'Formato de cédula recibido. La revisión documental final la realiza administración.'
                : 'La cédula debe tener entre 6 y 8 dígitos numéricos.')
            : 'Ingresa una cédula.',
    ]);
});

Route::view('/legal', 'legal.index')->name('legal.index');
Route::view('/legal/index', 'legal.index');
Route::view('/legal/view/aviso_emergencias', 'legal.view.aviso_emergencias');
Route::view('/legal/view/aviso_privacidad', 'legal.view.aviso_privacidad');
Route::view('/legal/view/condiciones_profesionales', 'legal.view.condiciones_profesionales');
Route::view('/legal/view/consentimiento_comunicaciones', 'legal.view.consentimiento_comunicaciones');
Route::view('/legal/view/consentimiento_datos_sensibles', 'legal.view.consentimiento_datos_sensibles');
Route::view('/legal/view/consentimiento_informado', 'legal.view.consentimiento_informado');
Route::view('/legal/view/politica_cancelaciones_reembolsos', 'legal.view.politica_cancelaciones_reembolsos');
Route::view('/legal/view/politica_cookies', 'legal.view.politica_cookies');
Route::view('/legal/view/reglas_comunidad', 'legal.view.reglas_comunidad');
Route::view('/legal/view/terminos_condiciones', 'legal.view.terminos_condiciones');
Route::view('/terminos-condiciones', 'terminos-condiciones');
Route::view('/terminos', 'legal.view.terminos_condiciones');
Route::view('/privacidad', 'legal.view.aviso_privacidad');
Route::view('/codigo-etica', 'legal.view.condiciones_profesionales');
Route::view('/aviso-privacidad-integral', 'legal.view.aviso_privacidad');
Route::view('/consentimiento-informado', 'legal.view.consentimiento_informado');
Route::view('/guia-integracion-consentimientos', 'legal.view.consentimiento_datos_sensibles');
Route::view('/PlantillasCorreos/01_verificar_correo', 'PlantillasCorreos.01_verificar_correo');
Route::view('/PlantillasCorreos/02_correo_verificado', 'PlantillasCorreos.02_correo_verificado');
Route::view('/PlantillasCorreos/03_frase_motivacional_diaria', 'PlantillasCorreos.03_frase_motivacional_diaria');

foreach ([
    '/login.html' => '/login', '/registro.html' => '/registro', '/recuperar.html' => '/recuperar', '/completar-perfil.html' => '/completar-perfil',
    '/paciente/dashboard-paciente.html' => '/paciente/dashboard-paciente',
    '/paciente/buscar-especialista.html' => '/paciente/buscar-especialista',
    '/paciente/agendar-cita.html' => '/paciente/agendar-cita',
    '/paciente/gestion-citas.html' => '/paciente/gestion-citas',
    '/paciente/diario-paciente.html' => '/paciente/diario-paciente',
    '/paciente/mis-tareas.html' => '/paciente/mis-tareas',
    '/paciente/perfil-paciente.html' => '/paciente/perfil-paciente',
    '/psicologo/dashboard-psicologo.html' => '/psicologo/dashboard-psicologo',
    '/psicologo/agenda-psicologo.html' => '/psicologo/agenda-psicologo',
    '/psicologo/pacientes-psicologo.html' => '/psicologo/pacientes-psicologo',
    '/psicologo/chat-profesional.html' => '/psicologo/chat-profesional',
    '/psicologo/diarios-autorizados.html' => '/psicologo/diarios-autorizados',
    '/psicologo/prescripciones.html' => '/psicologo/prescripciones',
    '/psicologo/perfil-psicologo.html' => '/psicologo/perfil-psicologo',
    '/psicologo/perfil-psicologo-sinsub.html' => '/psicologo/perfil-psicologo-sinsub',
] as $old => $new) {
    Route::redirect($old, $new);
}
