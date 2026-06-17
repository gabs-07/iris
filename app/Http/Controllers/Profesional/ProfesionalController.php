<?php

namespace App\Http\Controllers\Profesional;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DiaryEntry;
use App\Models\PatientNote;
use App\Models\Payment;
use App\Models\PatientProfile;
use App\Models\EmergencyContact;
use App\Models\PatientTask;
use App\Models\Prescription;
use App\Models\SessionNote;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AppointmentStatusUpdated;
use App\Services\PayPalClient;
use App\Services\AppointmentVideoService;
use App\Services\AppointmentLifecycleService;
use App\Services\AppointmentBusinessRules;
use App\Notifications\TaskAssigned;
use App\Support\ClinicalAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use RuntimeException;

class ProfesionalController extends Controller
{
    public function dashboard(): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(professionalId: Auth::id());

        $user = Auth::user()->load('professionalProfile');
        $appointments = $this->currentProfessionalAppointments()->with('patient')->latest('starts_at')->get();
        $today = $this->nowInAppTimezone()->toDateString();
        $todayAppointments = $this->currentProfessionalAppointments()
            ->with('patient')
            ->whereDate('starts_at', $today)
            ->whereIn('status', ['accepted', 'completed'])
            ->orderBy('starts_at')
            ->get();
        $pendingRequests = $this->currentProfessionalAppointments()
            ->with('patient')
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->orderBy('starts_at')
            ->get();
        $activePatientsCount = $this->patientsQuery()->count();
        $pendingTasksCount = PatientTask::where('professional_id', Auth::id())
            ->whereIn('status', ['entregada', 'requiere_cambios'])
            ->count();
        $notifications = Auth::user()->unreadNotifications()->latest()->take(5)->get();
        $activeAuxilioAppointments = $this->currentProfessionalAppointments()
            ->with('patient')
            ->whereIn('requested_by', ['auxilio', 'auxilio_invitado'])
            ->where('status', 'accepted')
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $this->nowInAppTimezone()->copy()->subMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES))
            ->latest('starts_at')
            ->get();

        return view('psicologo.dashboard-psicologo', compact(
            'user',
            'appointments',
            'todayAppointments',
            'pendingRequests',
            'activePatientsCount',
            'pendingTasksCount',
            'notifications',
            'activeAuxilioAppointments'
        ));
    }


    public function actualizarModoEscucha(Request $request): JsonResponse
    {
        $data = $request->validate([
            'modo_escucha_activo' => ['required', 'boolean'],
        ]);

        $profile = Auth::user()->professionalProfile()->firstOrCreate([
            'user_id' => Auth::id(),
        ], [
            'tipo_profesional' => Auth::user()->rol === 'psiquiatra' ? 'psiquiatra' : 'psicologo',
        ]);

        $active = (bool) $data['modo_escucha_activo'];
        $profile->forceFill([
            'modo_escucha_activo' => $active,
            'modo_escucha_activado_at' => $active ? now() : null,
        ])->save();

        return response()->json([
            'success' => true,
            'modo_escucha_activo' => $profile->modo_escucha_activo,
            'message' => $profile->modo_escucha_activo
                ? 'Modo Escucha activado. Puedes recibir solicitudes de auxilio.'
                : 'Modo Escucha desactivado.',
        ]);
    }

    public function agenda(): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(professionalId: Auth::id());

        $appointments = $this->currentProfessionalAppointments()
            ->with('patient')
            ->orderBy('starts_at')
            ->get();
        $patients = $this->patientsQuery()->get();
        $pendingRequests = $this->currentProfessionalAppointments()
            ->with('patient')
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->where('requested_by', 'paciente')
                    ->orWhereNotNull('reschedule_proposal');
            })
            ->whereIn('payment_status', ['paid', 'waived'])
            ->orderBy('starts_at')
            ->get();
        $resolvedRequests = $this->currentProfessionalAppointments()
            ->with('patient')
            ->whereIn('status', ['accepted', 'rejected', 'cancelled', 'completed', 'missed'])
            ->where('requested_by', 'paciente')
            ->latest('updated_at')
            ->take(20)
            ->get();
        $requestStats = [
            'new' => $pendingRequests->whereNull('reschedule_proposal')->count(),
            'reschedule' => $pendingRequests->whereNotNull('reschedule_proposal')->count(),
            'resolved' => $resolvedRequests->count(),
        ];

        return view('psicologo.agenda-psicologo', compact('appointments', 'patients', 'pendingRequests', 'resolvedRequests', 'requestStats'));
    }

    public function storeAgenda(Request $request): RedirectResponse
    {
        $patientIds = $this->patientsQuery()->pluck('id')->all();
        $data = $request->validate([
            'agenda_action' => ['nullable', Rule::in(['request_to_patient', 'schedule_direct'])],
            'title' => ['required', 'string', 'max:180'],
            'patient_id' => ['required', Rule::in($patientIds)],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'modality' => ['nullable', 'string', 'max:80'],
            'room' => ['nullable', 'string', 'max:80'],
            'room_link' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $startsAt = $this->parseAppointmentStart($data['date'], $data['time']);
        if ($startsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['time' => 'Selecciona una fecha y hora futura para la cita.'])->withInput();
        }

        $patient = User::where('rol', 'paciente')->whereKey((int) $data['patient_id'])->firstOrFail();
        app(AppointmentBusinessRules::class)->validateAppointment($patient, Auth::user(), $startsAt, $data['modality'] ?? $data['room'] ?? 'Videollamada');

        $isDirectSchedule = ($data['agenda_action'] ?? 'request_to_patient') === 'schedule_direct';

        $appointment = Appointment::create([
            'patient_id' => (int) $data['patient_id'],
            'professional_id' => Auth::id(),
            'folio' => ($isDirectSchedule ? 'SES-' : 'SOL-').now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'reason' => $data['title'],
            'modality' => $data['modality'] ?? $data['room'] ?? 'Videollamada',
            'appointment_date' => $data['date'],
            'appointment_time' => $data['time'],
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addMinutes(50),
            'notes' => $data['notes'] ?? null,
            'room_link' => $data['room_link'] ?? null,
            'status' => $isDirectSchedule ? 'accepted' : 'pending',
            'payment_status' => 'waived',
            'requested_by' => 'profesional',
        ]);

        if ($isDirectSchedule) {
            try {
                app(AppointmentVideoService::class)->ensureZoomMeeting($appointment);
                $appointment->refresh();
            } catch (RuntimeException $exception) {
                return back()->withErrors(['zoom' => 'La sesión se guardó, pero no se pudo crear la videollamada de Zoom: '.$exception->getMessage()]);
            }
        }

        \App\Support\SafeNotifier::notify($appointment->patient, new AppointmentStatusUpdated($appointment, $isDirectSchedule ? 'accepted' : 'requested_by_professional'));
        ClinicalAudit::log($isDirectSchedule ? 'appointment.created_by_professional' : 'appointment.requested_by_professional', $appointment->patient_id, $appointment, $isDirectSchedule ? 'Profesional creó sesión directa en agenda.' : 'Profesional solicitó una cita al paciente desde la agenda.');

        return back()->with('success', $isDirectSchedule ? 'Sesión agregada a tu agenda.' : 'Solicitud de cita enviada al paciente para confirmación.');
    }

    public function solicitudes(): View
    {
        $appointments = $this->currentProfessionalAppointments()->where('status', 'pending')->with('patient')->latest()->get();
        return view('psicologo.solicitudes-psicologo', compact('appointments'));
    }

    public function updateSolicitud(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->professional_id === Auth::id(), 403);
        abort_unless(in_array($appointment->payment_status, ['paid', 'waived'], true), 422, 'Solo puedes responder solicitudes pagadas o de seguimiento sin cobro.');

        $data = $request->validate([
            'action' => ['required', 'in:accepted,rescheduled,rejected'],
            'reschedule_proposal' => ['nullable', 'required_if:action,rescheduled', 'string', 'max:1000'],
            'reschedule_date' => ['nullable', 'required_if:action,rescheduled', 'date', 'after_or_equal:today'],
            'reschedule_time' => ['nullable', 'required_if:action,rescheduled', 'string', 'max:20'],
            'cancel_reason' => ['nullable', 'required_if:action,rejected', 'string', 'max:1000'],
            'room_link' => ['nullable', 'url', 'max:500'],
        ]);

        if ($data['action'] === 'rejected') {
            $this->refundAppointmentPayment($appointment, $data['cancel_reason'] ?? 'Solicitud rechazada por el profesional.');
        }

        if ($data['action'] === 'rescheduled') {
            $rescheduleStartsAt = $this->parseAppointmentStart($data['reschedule_date'], $data['reschedule_time']);
            if ($rescheduleStartsAt->lt($this->nowInAppTimezone())) {
                return back()->withErrors(['reschedule_time' => 'Selecciona una fecha y hora futura para reagendar.'])->withInput();
            }
            app(AppointmentBusinessRules::class)->validateAppointment($appointment->patient, Auth::user(), $rescheduleStartsAt, $appointment->modality, $appointment->id);
        }

        if ($data['action'] === 'accepted') {
            $validationStart = $appointment->reschedule_date && $appointment->reschedule_time
                ? $this->parseAppointmentStart($appointment->reschedule_date->toDateString(), $appointment->reschedule_time)
                : $this->appointmentStartForValidation($appointment);

            if ($validationStart) {
                if ($validationStart->lt($this->nowInAppTimezone())) {
                    return back()->withErrors(['appointment' => 'No puedes aceptar una cita que ya pasó. Propón un nuevo horario.'])->withInput();
                }
                app(AppointmentBusinessRules::class)->validateAppointment($appointment->patient, Auth::user(), $validationStart, $appointment->modality, $appointment->id);
            }
        }

        $updates = [
            'status' => $data['action'],
            'reschedule_proposal' => $data['reschedule_proposal'] ?? null,
            'reschedule_date' => $data['action'] === 'rescheduled' ? $data['reschedule_date'] : null,
            'reschedule_time' => $data['action'] === 'rescheduled' ? $data['reschedule_time'] : null,
            'cancel_reason' => $data['cancel_reason'] ?? null,
            'room_link' => $data['room_link'] ?? $appointment->room_link,
            'payment_status' => $data['action'] === 'rejected' ? 'refunded' : $appointment->payment_status,
        ];

        if ($data['action'] === 'accepted' && $appointment->reschedule_date && $appointment->reschedule_time) {
            $startsAt = $this->parseAppointmentStart($appointment->reschedule_date->toDateString(), $appointment->reschedule_time);
            $updates['appointment_date'] = $appointment->reschedule_date;
            $updates['appointment_time'] = $appointment->reschedule_time;
            $updates['starts_at'] = $startsAt;
            $updates['ends_at'] = (clone $startsAt)->addMinutes(50);
            $updates['reschedule_proposal'] = null;
            $updates['reschedule_date'] = null;
            $updates['reschedule_time'] = null;
        }

        $appointment->update($updates);
        $appointment->refresh();

        if ($data['action'] === 'accepted') {
            try {
                app(AppointmentVideoService::class)->ensureZoomMeeting($appointment);
                $appointment->refresh();
            } catch (RuntimeException $exception) {
                return back()->withErrors(['zoom' => 'La solicitud fue aceptada, pero no se pudo crear la videollamada de Zoom: '.$exception->getMessage()]);
            }
        }

        \App\Support\SafeNotifier::notify($appointment->patient, new AppointmentStatusUpdated($appointment->fresh(), $data['action']));
        ClinicalAudit::log('appointment.request.updated', $appointment->patient_id, $appointment, 'Profesional respondió solicitud: '.$data['action']);

        return back()->with('success', 'Solicitud actualizada y notificada al paciente.');
    }

    public function pacientes(): View
    {
        $patients = $this->patientsQuery()
            ->with(['patientProfile', 'emergencyContact'])
            ->get();
        $tasks = PatientTask::where('professional_id', Auth::id())
            ->with('patient')
            ->latest('submitted_at')
            ->latest()
            ->get();
        $tasksForReview = $tasks->whereIn('status', ['entregada'])->values();
        $notes = PatientNote::where('professional_id', Auth::id())->with('patient')->latest()->get();
        $appointments = $this->currentProfessionalAppointments()->with('patient')->latest('starts_at')->get();
        $stats = [
            'total' => $patients->count(),
            'active' => $patients->filter(fn ($patient) => $appointments->where('patient_id', $patient->id)->whereIn('status', ['accepted', 'completed'])->isNotEmpty())->count(),
            'pending_tasks' => $tasks->whereIn('status', ['pendiente', 'requiere_cambios', 'entregada'])->count(),
            'high_risk' => $notes->filter(fn ($note) => str_contains(mb_strtolower((string) $note->description), 'riesgo alto'))->count(),
        ];

        return view('psicologo.pacientes-psicologo', compact('patients', 'tasks', 'tasksForReview', 'notes', 'appointments', 'stats'));
    }


    public function diariosAutorizados(): View
    {
        $diaryEntries = DiaryEntry::query()
            ->with(['patient', 'authorizedProfessional'])
            ->where('authorized_professional_id', Auth::id())
            ->latest('entry_date')
            ->paginate(20);

        return view('psicologo.diarios-autorizados', compact('diaryEntries'));
    }

    public function storePacienteData(Request $request): RedirectResponse
    {
        if ($request->input('form_type') === 'new_patient') {
            $data = $request->validate([
                'nombre' => ['required', 'string', 'max:120'],
                'apellidos' => ['required', 'string', 'max:160'],
                'email' => ['nullable', 'email', 'max:255'],
                'telefono' => ['nullable', 'string', 'max:30'],
                'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
                'motivo_consulta' => ['nullable', 'string', 'max:3000'],
                'objetivos' => ['nullable', 'string', 'max:3000'],
                'emergencia_nombre' => ['nullable', 'string', 'max:180'],
                'emergencia_telefono' => ['nullable', 'string', 'max:30'],
            ]);

            $email = $data['email'] ?: 'paciente.manual+'.Str::lower(Str::random(8)).'@iris.local';
            $patient = User::firstOrCreate(['email' => $email], [
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'name' => $data['nombre'].' '.$data['apellidos'],
                'password' => Hash::make(Str::random(32)),
                'rol' => 'paciente',
                'telefono' => $data['telefono'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'profile_completed' => true,
                'professional_status' => 'none',
                'email_verified_at' => now(),
            ]);

            $patient->update([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'name' => $data['nombre'].' '.$data['apellidos'],
                'telefono' => $data['telefono'] ?? $patient->telefono,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? $patient->fecha_nacimiento,
                'profile_completed' => true,
            ]);

            PatientProfile::updateOrCreate(['user_id' => $patient->id], [
                'motivo_consulta' => $data['motivo_consulta'] ?? 'Expediente digitalizado por el especialista.',
                'objetivos' => $data['objetivos'] ?? null,
                'terapia_previa' => 'si',
            ]);

            if (($data['emergencia_nombre'] ?? null) || ($data['emergencia_telefono'] ?? null)) {
                EmergencyContact::updateOrCreate(['user_id' => $patient->id], [
                    'nombre' => $data['emergencia_nombre'] ?? 'Contacto no especificado',
                    'relacion' => 'Emergencia',
                    'telefono' => $data['emergencia_telefono'] ?? '',
                ]);
            }

            Appointment::firstOrCreate([
                'patient_id' => $patient->id,
                'professional_id' => Auth::id(),
                'folio' => 'EXP-MANUAL-'.$patient->id.'-'.Auth::id(),
            ], [
                'reason' => 'Alta manual de expediente',
                'modality' => 'Seguimiento digitalizado',
                'appointment_date' => today(),
                'appointment_time' => now()->format('H:i'),
                'starts_at' => now(),
                'ends_at' => now()->addMinutes(50),
                'status' => 'completed',
                'payment_status' => 'waived',
                'amount' => 0,
                'requested_by' => 'profesional',
                'notes' => 'Paciente agregado manualmente para digitalizar seguimiento previo.',
            ]);

            ClinicalAudit::log('patient.manual_linked', $patient->id, $patient, 'Profesional agregó paciente para digitalizar seguimiento.');
            return back()->with('success', 'Paciente agregado a tu lista de seguimiento.');
        }

        $patientIds = $this->patientsQuery()->pluck('id')->all();

        if ($request->input('form_type') === 'clinical_history') {
            $rules = [
                'patient_id' => ['required', Rule::in($patientIds)],
                'descripcion_documento_adjunto' => ['nullable', 'string', 'max:2000'],
                'archivos_paciente' => ['nullable', 'array'],
                'archivos_paciente.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp,xlsx,xls', 'max:5120'],
            ];

            foreach ($this->clinicalHistoryFields() as $field) {
                $rules[$field] = ['nullable', 'string', 'max:6000'];
            }

            $data = $request->validate($rules);
            $patient = User::where('rol', 'paciente')->whereIn('id', $patientIds)->findOrFail((int) $data['patient_id']);
            $profile = PatientProfile::firstOrCreate(['user_id' => $patient->id]);
            $history = (array) ($profile->clinical_history ?? []);

            foreach ($this->clinicalHistoryFields() as $field) {
                if ($request->has($field)) {
                    $history[$field] = $data[$field] ?? null;
                }
            }

            $attachments = (array) ($profile->clinical_attachments ?? []);
            foreach ($request->file('archivos_paciente', []) as $file) {
                $attachments[] = [
                    'nombre_original' => $file->getClientOriginalName(),
                    'ruta' => $file->store('clinical-attachments/'.$patient->id, 'local'),
                    'disk' => 'local',
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'descripcion' => $data['descripcion_documento_adjunto'] ?? null,
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }

            $profile->update([
                'motivo_consulta' => $history['motivo_consulta'] ?? $profile->motivo_consulta,
                'objetivos' => $history['objetivos_paciente'] ?? $history['objetivos_generales'] ?? $profile->objetivos,
                'ocupacion' => $history['paciente_ocupacion'] ?? $profile->ocupacion,
                'domicilio' => $history['paciente_domicilio'] ?? $profile->domicilio,
                'estado_civil' => $history['paciente_estado_civil'] ?? $profile->estado_civil,
                'antecedentes' => $history['antecedentes_personales_patologicos'] ?? $history['antecedentes_psicologicos'] ?? $profile->antecedentes,
                'alergias' => $history['alergias'] ?? $profile->alergias,
                'medicacion_actual' => $history['medicacion_actual'] ?? $profile->medicacion_actual,
                'clinical_history' => $history,
                'clinical_attachments' => $attachments,
            ]);

            ClinicalAudit::log('clinical_history.updated', $patient->id, $profile, 'Profesional actualizó historial clínico psicológico.');
            return back()->with('success', 'Historial clínico guardado correctamente.');
        }

        if ($request->input('form_type') === 'task' || $request->input('kind') === 'task' || ($request->filled('title') && $request->filled('dueDate'))) {
            $data = $request->validate([
                'patient_id' => ['required', Rule::in($patientIds)],
                'title' => ['required', 'string', 'max:180'],
                'dueDate' => ['nullable', 'date', 'after_or_equal:today'],
                'date' => ['nullable', 'date', 'after_or_equal:today'],
                'description' => ['nullable', 'string', 'max:3000'],
                'repeat' => ['nullable', 'string', 'max:80'],
            ]);
            $task = PatientTask::create([
                'patient_id' => (int) $data['patient_id'],
                'professional_id' => Auth::id(),
                'title' => $data['title'],
                'due_date' => $data['dueDate'] ?? $data['date'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'pendiente',
                'review_status' => 'pendiente',
                'repeat' => $data['repeat'] ?? null,
            ]);
            \App\Support\SafeNotifier::notify($task->patient, new TaskAssigned($task));
            ClinicalAudit::log('task.assigned', $task->patient_id, $task, 'Profesional asignó tarea terapéutica.');
            return back()->with('success', 'Tarea asignada y notificada al paciente.');
        }

        $data = $request->validate([
            'patient_id' => ['required', Rule::in($patientIds)],
            'title' => ['required', 'string', 'max:180'],
            'date' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:3000'],
        ]);

        $note = PatientNote::create([
            'patient_id' => (int) $data['patient_id'],
            'professional_id' => Auth::id(),
            'title' => $data['title'],
            'note_date' => $data['date'] ?? today(),
            'type' => $data['type'] ?? 'clínica',
            'description' => $data['description'],
        ]);
        ClinicalAudit::log('clinical_note.created', $note->patient_id, $note, 'Profesional creó nota clínica.');
        return back()->with('success', 'Nota clínica guardada.');
    }

    public function reviewTask(Request $request, PatientTask $task): RedirectResponse
    {
        abort_unless($task->professional_id === Auth::id(), 403);
        abort_unless($task->status === 'entregada', 403, 'Solo puedes revisar tareas entregadas.');

        $data = $request->validate([
            'review_action' => ['required', Rule::in(['aprobada', 'requiere_cambios'])],
            'review_feedback' => ['nullable', 'string', 'max:3000'],
        ]);

        $approved = $data['review_action'] === 'aprobada';

        $task->update([
            'status' => $approved ? 'completada' : 'requiere_cambios',
            'review_status' => $data['review_action'],
            'review_feedback' => $data['review_feedback'] ?? null,
            'reviewed_at' => now(),
            'completed_at' => $approved ? now() : null,
        ]);

        if ($task->patient) {
            \App\Support\SafeNotifier::notify($task->patient, new \App\Notifications\TaskReviewed($task->fresh()));
        }

        ClinicalAudit::log('task.reviewed', $task->patient_id, $task, 'Profesional revisó tarea terapéutica: '.$data['review_action']);
        return back()->with('success', $approved ? 'Tarea aprobada.' : 'Tarea devuelta para cambios.');
    }

    public function viewTaskPdf(PatientTask $task)
    {
        abort_unless($task->professional_id === Auth::id(), 403);

        ClinicalAudit::log('task.pdf.viewed_by_professional', $task->patient_id, $task, 'Profesional visualizó PDF adjunto a tarea.');

        return $this->taskPdfResponse($task);
    }

    private function taskPdfResponse(PatientTask $task)
    {
        abort_unless($task->evidence_file_path, 404, 'La tarea no tiene PDF adjunto.');

        $disk = $task->evidence_file_disk ?: 'local';
        $path = $task->evidence_file_path;
        $absolutePath = null;

        if (Storage::disk($disk)->exists($path)) {
            $absolutePath = Storage::disk($disk)->path($path);
        } elseif (Storage::disk('local')->exists($path)) {
            $absolutePath = Storage::disk('local')->path($path);
        } elseif (Storage::disk('public')->exists($path)) {
            $absolutePath = Storage::disk('public')->path($path);
        }

        abort_unless($absolutePath && is_file($absolutePath), 404, 'No se encontró el PDF de la tarea.');

        $fileName = $this->safePdfFileName($task->evidence_file_name ?: 'evidencia-tarea.pdf');

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safePdfFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9_. -]/', '_', $fileName) ?: 'evidencia-tarea.pdf';

        return Str::endsWith(Str::lower($fileName), '.pdf') ? $fileName : $fileName.'.pdf';
    }

    public function viewClinicalAttachment(User $patient, int $index)
    {
        abort_unless($patient->rol === 'paciente', 404);
        abort_unless($this->patientsQuery()->where('users.id', $patient->id)->exists(), 403);

        $profile = $patient->patientProfile;
        $attachments = (array) ($profile?->clinical_attachments ?? []);
        $attachment = $attachments[$index] ?? null;
        abort_unless($attachment && ! empty($attachment['ruta']), 404, 'No se encontró el archivo clínico.');

        $disk = $attachment['disk'] ?? 'local';
        abort_unless(Storage::disk($disk)->exists($attachment['ruta']), 404, 'No se encontró el archivo clínico.');

        ClinicalAudit::log('clinical_attachment.viewed', $patient->id, $profile, 'Profesional visualizó adjunto del historial clínico.');

        $fileName = preg_replace('/[^A-Za-z0-9_. -]/', '_', $attachment['nombre_original'] ?? 'adjunto-clinico') ?: 'adjunto-clinico';
        return response()->file(Storage::disk($disk)->path($attachment['ruta']), [
            'Content-Type' => $attachment['mime'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function prescripciones(): View
    {
        abort_unless(Auth::user()->rol === 'psiquiatra', 403, 'Solo psiquiatras pueden acceder a prescripciones farmacológicas.');
        $patients = $this->patientsQuery()->get();
        $prescriptions = Prescription::where('professional_id', Auth::id())->with('patient')->latest()->get();
        $currentRole = 'psychiatrist';
        return view('psicologo.prescripciones', compact('patients', 'prescriptions', 'currentRole'));
    }

    public function storePrescripcion(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->rol === 'psiquiatra', 403, 'Solo un psiquiatra puede emitir prescripciones farmacológicas.');
        $patientIds = $this->patientsQuery()->pluck('id')->all();
        $data = $request->validate([
            'patient_id' => ['required', Rule::in($patientIds)],
            'diagnosis' => ['required', 'string', 'max:255'],
            'medication' => ['nullable', 'string', 'max:255'],
            'medicamento' => ['nullable', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:120'],
            'dosis' => ['nullable', 'string', 'max:120'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'frecuencia' => ['nullable', 'string', 'max:120'],
            'duration' => ['nullable', 'string', 'max:120'],
            'duracion' => ['nullable', 'string', 'max:120'],
            'instructions' => ['required', 'string', 'max:3000'],
            'indicaciones' => ['nullable', 'string', 'max:3000'],
        ]);

        $medication = $data['medication'] ?? $data['medicamento'] ?? null;
        abort_if(! $medication, 422, 'El medicamento es obligatorio.');
        $patient = User::findOrFail((int) $data['patient_id']);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'professional_id' => Auth::id(),
            'folio' => 'RX-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'patient_name' => $patient->nombre_completo,
            'diagnosis' => $data['diagnosis'],
            'medication' => $medication,
            'dose' => $data['dose'] ?? $data['dosis'] ?? null,
            'frequency' => $data['frequency'] ?? $data['frecuencia'] ?? null,
            'duration' => $data['duration'] ?? $data['duracion'] ?? null,
            'instructions' => $data['instructions'] ?? $data['indicaciones'] ?? null,
            'status' => 'emitida',
            'issued_at' => now(),
        ]);

        ClinicalAudit::log('prescription.created', $patient->id, $prescription, 'Psiquiatra emitió prescripción.');
        return back()->with('success', 'Prescripción emitida.');
    }

    public function destroyPrescripcion(Prescription $prescription): RedirectResponse
    {
        abort_unless(Auth::user()->rol === 'psiquiatra', 403, 'Solo un psiquiatra puede eliminar prescripciones.');
        abort_unless($prescription->professional_id === Auth::id(), 403);
        ClinicalAudit::log('prescription.deleted', $prescription->patient_id, $prescription, 'Psiquiatra eliminó prescripción.');
        $prescription->delete();
        return back()->with('success', 'Prescripción eliminada.');
    }

    public function perfilSinSub(): View
    {
        $user = Auth::user()->load('professionalProfile');
        return view('psicologo.perfil-psicologo-sinsub', compact('user'));
    }

    public function perfil(): View
    {
        $user = Auth::user()->load('professionalProfile');
        $subscription = Subscription::where('user_id', Auth::id())->latest()->first();
        return view('psicologo.perfil-psicologo', compact('user', 'subscription'));
    }

    public function updatePerfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'max:60'],
            'emergencia_nombre' => ['nullable', 'string', 'max:180'],
            'emergencia_relacion' => ['nullable', 'string', 'max:80'],
            'emergencia_telefono' => ['nullable', 'string', 'max:30'],
            'titulo_profesional' => ['nullable', 'string', 'max:180'],
            'especialidad_principal' => ['nullable', 'string', 'max:180'],
            'cedula_profesional' => ['nullable', 'string', 'max:60'],
            'cedula_especialidad' => ['nullable', 'string', 'max:60'],
            'institucion' => ['nullable', 'string', 'max:180'],
            'posgrado' => ['nullable', 'string', 'max:180'],
            'experiencia_anios' => ['nullable'],
            'asociaciones' => ['nullable', 'string', 'max:255'],
            'modalidad' => ['nullable', 'string', 'max:60'],
            'ubicacion' => ['nullable', 'string', 'max:180'],
            'idiomas' => ['nullable', 'string', 'max:120'],
            'biografia' => ['nullable', 'string', 'max:3000'],
            'servicios' => ['nullable', 'string', 'max:3000'],
            'presentacion' => ['nullable', 'string', 'max:3000'],
            'formacion_academica_text' => ['nullable', 'string', 'max:3000'],
            'especialidades_text' => ['nullable', 'string', 'max:1000'],
            'dias_atencion' => ['nullable', 'array'],
            'dias_atencion.*' => ['string', Rule::in(['lunes','martes','miércoles','jueves','viernes','sábado','domingo'])],
            'proximo_espacio' => ['nullable', 'string', 'max:180'],
            'costo_min' => ['nullable', 'numeric', 'min:0'],
            'costo_max' => ['nullable', 'numeric', 'min:0', 'gte:costo_min'],
            'duracion_sesion' => ['nullable', 'integer', 'min:30', 'max:180'],
            'enfoques' => ['nullable', 'array'],
            'poblaciones' => ['nullable', 'array'],
            'areas' => ['nullable', 'array'],
            'disponibilidad' => ['nullable', 'array'],
            'disponibilidad.*.inicio' => ['nullable', 'date_format:H:i'],
            'disponibilidad.*.fin' => ['nullable', 'date_format:H:i'],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if (isset($data['experiencia_anios']) && ! is_numeric($data['experiencia_anios'])) {
            preg_match('/\d+/', (string) $data['experiencia_anios'], $matches);
            $data['experiencia_anios'] = isset($matches[0]) ? (int) $matches[0] : null;
        }

        app(AppointmentBusinessRules::class)->validateProfessionalProfileSchedule($data);

        $user = Auth::user()->load('professionalProfile', 'emergencyContact');

        $userPayload = array_filter($data, fn ($key) => in_array($key, ['nombre', 'apellidos', 'telefono', 'genero', 'fecha_nacimiento'], true), ARRAY_FILTER_USE_KEY);
        if (! empty($data['nombre']) || ! empty($data['apellidos'])) {
            $userPayload['name'] = trim(($data['nombre'] ?? $user->nombre).' '.($data['apellidos'] ?? $user->apellidos));
        }
        if (! empty($data['password'])) {
            $userPayload['password'] = Hash::make($data['password']);
        }

        if (array_key_exists('emergencia_nombre', $data) || array_key_exists('emergencia_telefono', $data)) {
            $user->emergencyContact()->updateOrCreate(['user_id' => $user->id], [
                'nombre' => $data['emergencia_nombre'] ?? $user->emergencyContact?->nombre ?? '',
                'relacion' => $data['emergencia_relacion'] ?? $user->emergencyContact?->relacion ?? '',
                'telefono' => $data['emergencia_telefono'] ?? $user->emergencyContact?->telefono ?? '',
            ]);
        }

        $documents = $user->professionalProfile?->documentos ?? [];
        foreach ($request->file('documentos', []) as $file) {
            $documents[] = [
                'nombre_original' => $file->getClientOriginalName(),
                'ruta' => $file->store('professional-documents/'.$user->id, 'public'),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        $data['modalidad'] = $data['modalidad'] ?? $user->professionalProfile?->modalidad ?? 'ambas';
        $data['formacion_academica'] = $this->linesToArray($data['formacion_academica_text'] ?? null);
        $data['especialidades'] = $this->csvToArray($data['especialidades_text'] ?? null);
        unset($data['formacion_academica_text'], $data['especialidades_text'], $data['nombre'], $data['apellidos'], $data['telefono'], $data['fecha_nacimiento'], $data['genero'], $data['emergencia_nombre'], $data['emergencia_relacion'], $data['emergencia_telefono'], $data['password'], $data['password_confirmation'], $data['documentos']);

        $requiredDocuments = count($documents) > 0;
        $isComplete = filled($userPayload['name'] ?? $user->name)
            && filled($userPayload['telefono'] ?? $user->telefono)
            && filled($data['titulo_profesional'] ?? $user->professionalProfile?->titulo_profesional)
            && filled($data['especialidad_principal'] ?? $user->professionalProfile?->especialidad_principal)
            && filled($data['cedula_profesional'] ?? $user->professionalProfile?->cedula_profesional)
            && filled($data['institucion'] ?? $user->professionalProfile?->institucion)
            && filled($data['biografia'] ?? $user->professionalProfile?->biografia)
            && filled($data['servicios'] ?? $user->professionalProfile?->servicios)
            && filled($data['modalidad'] ?? $user->professionalProfile?->modalidad)
            && filled($data['ubicacion'] ?? $user->professionalProfile?->ubicacion)
            && filled($data['idiomas'] ?? $user->professionalProfile?->idiomas)
            && filled($data['costo_min'] ?? $user->professionalProfile?->costo_min)
            && filled($data['duracion_sesion'] ?? $user->professionalProfile?->duracion_sesion)
            && ! empty($data['formacion_academica'] ?? $user->professionalProfile?->formacion_academica ?? [])
            && ! empty($data['especialidades'] ?? $user->professionalProfile?->especialidades ?? [])
            && ! empty($data['dias_atencion'] ?? $user->professionalProfile?->dias_atencion ?? [])
            && $requiredDocuments;

        $profilePayload = array_merge($data, [
            'tipo_profesional' => $user->rol,
            'documentos' => $documents,
        ]);

        if ($isComplete) {
            $profilePayload = array_merge($profilePayload, [
                'submitted_at' => now(),
                'approved_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'approved_by' => null,
            ]);
        }

        $profile = $user->professionalProfile()->updateOrCreate(['user_id' => $user->id], $profilePayload);

        $status = $isComplete ? 'pending' : 'incomplete';
        $userPayload['profile_completed'] = $isComplete;
        $userPayload['professional_status'] = $status;
        $userPayload['professional_submitted_at'] = $isComplete ? now() : null;
        $userPayload['professional_rejected_at'] = null;
        $userPayload['professional_rejection_reason'] = null;
        $userPayload['professional_approved_at'] = null;
        $userPayload['approved_by'] = null;
        $user->update($userPayload);

        if ($isComplete) {
            $this->notifyAdminsProfileSubmitted($user->fresh());
            ClinicalAudit::log('professional.profile.submitted', null, $profile, 'Profesional actualizó y envió perfil para revisión.');
            return back()->with('success', 'Perfil profesional guardado y enviado al administrador para autorización.');
        }

        ClinicalAudit::log('professional.profile.draft_saved', null, $profile, 'Profesional guardó avance de perfil sin enviarlo a autorización.');
        return back()->with('success', 'Avance de perfil guardado. Cuando completes los campos obligatorios se enviará a revisión.');
    }

    public function pagoSuscripcion(): View|RedirectResponse
    {
        abort_unless(Auth::user()->isProfesional(), 403);
        if (Auth::user()->professional_status !== 'approved') {
            return redirect()->route('profesional.perfil')
                ->with('warning', 'La suscripción se habilita hasta que administración apruebe tu perfil profesional.');
        }
        $cycle = request('cycle', 'monthly') === 'annual' ? 'annual' : 'monthly';
        $plan = [
            'name' => $cycle === 'annual' ? 'Plan Anual IRIS' : 'Plan Mensual IRIS',
            'amount' => $cycle === 'annual' ? 7680 : 10,
            'cycle' => $cycle,
            'features' => 'Agenda, pacientes, solicitudes de cita, tareas clínicas, perfil público y soporte.',
        ];
        return view('psicologo.pago-suscripcion', compact('plan'));
    }

    public function storePagoSuscripcion(Request $request): RedirectResponse
    {
        $cycle = $request->input('cycle', 'monthly') === 'annual' ? 'annual' : 'monthly';
        return redirect('/psicologo/pago-suscripcion?cycle='.$cycle);
    }

    public function sesion(): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(professionalId: Auth::id());

        $appointments = $this->currentProfessionalAppointments()->with('patient')->whereIn('status', ['accepted', 'completed'])->latest('starts_at')->get();
        return view('psicologo.sesion', compact('appointments'));
    }

    public function storeSesion(Request $request): RedirectResponse
    {
        $appointmentIds = $this->currentProfessionalAppointments()->pluck('id')->all();
        $data = $request->validate([
            'appointment_id' => ['required', Rule::in($appointmentIds)],
            'note_type' => ['nullable', 'in:session,patient'],
            'notes' => ['required', 'string', 'max:10000'],
        ]);

        $appointment = Appointment::findOrFail((int) $data['appointment_id']);
        $note = SessionNote::create([
            'appointment_id' => $appointment->id,
            'professional_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'note_type' => $data['note_type'] ?? 'session',
            'content' => $data['notes'],
        ]);

        ClinicalAudit::log('session_note.created', $appointment->patient_id, $note, 'Profesional guardó nota de sesión.');
        return back()->with('success', 'Nota de sesión guardada.');
    }

    private function appointmentStartForValidation(Appointment $appointment): ?Carbon
    {
        if ($appointment->starts_at) {
            return $appointment->starts_at->copy()->timezone($this->appTimezone());
        }

        if ($appointment->appointment_date && $appointment->appointment_time) {
            return $this->parseAppointmentStart($appointment->appointment_date->toDateString(), $appointment->appointment_time);
        }

        return null;
    }

    private function currentProfessionalAppointments()
    {
        return Appointment::query()->where('professional_id', Auth::id());
    }

    private function patientsQuery()
    {
        $ids = Appointment::where('professional_id', Auth::id())
            ->whereIn('status', ['accepted', 'completed', 'missed'])
            ->pluck('patient_id')
            ->unique();

        return User::whereIn('id', $ids)->where('rol', 'paciente');
    }

    private function refundAppointmentPayment(Appointment $appointment, string $reason): void
    {
        $payment = Payment::where('appointment_id', $appointment->id)
            ->where('status', 'paid')
            ->where('provider', 'paypal')
            ->latest()
            ->first();

        abort_unless($payment && $payment->provider_capture_id, 422, 'No se encontró una captura PayPal válida para reembolsar esta cita.');

        if (str_starts_with((string) $payment->provider_capture_id, 'LOCAL_')) {
            $refund = ['id' => 'LOCAL_REFUND_'.Str::upper(Str::random(8)), 'status' => 'COMPLETED', 'reason' => $reason, 'local_refund' => true];
        } else {
            try {
                $refund = app(PayPalClient::class)->refundCapture($payment->provider_capture_id, (float) $payment->amount, (string) $payment->currency, $reason);
            } catch (\RuntimeException $exception) {
                abort(422, $exception->getMessage());
            }
        }

        $payment->update([
            'status' => 'refunded',
            'provider_payload' => array_merge($payment->provider_payload ?? [], ['refund' => $refund]),
        ]);

        ClinicalAudit::log('payment.refunded', $appointment->patient_id, $payment, 'Pago PayPal reembolsado por rechazo de cita.');
    }


    private function parseAppointmentStart(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.$time, $this->appTimezone());
    }

    private function nowInAppTimezone(): Carbon
    {
        return now($this->appTimezone());
    }

    private function appTimezone(): string
    {
        return (string) config('app.timezone', 'America/Mexico_City');
    }

    private function clinicalHistoryFields(): array
    {
        return [
            'paciente_nombre','paciente_edad','paciente_fecha_nacimiento','paciente_sexo','paciente_genero','paciente_curp','paciente_telefono','paciente_correo','paciente_domicilio','paciente_ocupacion','paciente_estado_civil','contacto_emergencia_nombre','contacto_emergencia_telefono','contacto_emergencia_parentesco',
            'motivo_consulta','inicio_padecimiento','curso_padecimiento','descripcion_padecimiento_actual','impacto_funcional','objetivos_paciente',
            'antecedentes_heredofamiliares','antecedentes_personales_patologicos','antecedentes_personales_no_patologicos','alergias','medicacion_actual','consumo_sustancias','hospitalizaciones_previas','antecedentes_psicologicos','dinamica_familiar_red_apoyo',
            'sueno','apetito','energia','sintomas_fisicos','signos_vitales','peso_talla',
            'habitus_exterior','actitud_entrevista','orientacion','atencion_concentracion','memoria','lenguaje','estado_animo','afecto','pensamiento','percepcion','juicio','insight','pruebas_aplicadas','resultados_interpretacion',
            'problemas_clinicos','impresion_diagnostica','codigo_diagnostico','diagnostico_diferencial','pronostico','observaciones_diagnosticas',
            'nivel_riesgo_general','riesgo_suicida','riesgo_autolesion','riesgo_terceros','ideacion_suicida_actual','intentos_previos','factores_riesgo','factores_protectores','plan_seguridad','canalizacion_interconsulta',
            'objetivos_generales','objetivos_especificos','intervenciones_terapeuticas','enfoque_terapeutico','frecuencia_sesiones','duracion_estimada','indicaciones_terapeuticas','tareas_para_casa','indicadores_progreso','criterios_alta',
        ];
    }

    private function linesToArray(?string $value): array
    {
        return collect(preg_split('/\R+/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function csvToArray(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function notifyAdminsProfileSubmitted(User $professional): void
    {
        foreach (User::where('rol', 'admin')->get() as $admin) {
            \App\Support\SafeNotifier::notify($admin, new \App\Notifications\ProfessionalProfileSubmitted($professional));
        }
    }
}
