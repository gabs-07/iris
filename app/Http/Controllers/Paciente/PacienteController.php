<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DiaryEntry;
use App\Models\PatientTask;
use App\Models\User;
use App\Notifications\AppointmentStatusUpdated;
use App\Services\AppointmentVideoService;
use App\Services\AppointmentLifecycleService;
use App\Services\AppointmentBusinessRules;
use App\Services\AuxilioSessionService;
use App\Support\ClinicalAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use RuntimeException;

class PacienteController extends Controller
{
    public function dashboard(): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(patientId: Auth::id());
        $user = Auth::user();
        $now = $this->nowInAppTimezone();
        $appointments = $user->patientAppointments()
            ->with('professional.professionalProfile')
            ->whereNotIn('status', ['cancelled', 'rejected', 'completed', 'missed'])
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '>=', $now->copy()->subMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES));
            })
            ->orderByRaw('starts_at IS NULL, starts_at ASC')
            ->take(5)
            ->get();
        $tasks = $user->tasksAssigned()->latest('due_date')->take(6)->get();
        return view('paciente.dashboard-paciente', compact('appointments', 'tasks'));
    }

    public function buscarEspecialista(): View
    {
        $professionals = $this->approvedActiveProfessionalsQuery()
            ->with('professionalProfile')
            ->latest('professional_approved_at')
            ->get();

        return view('paciente.buscar-especialista', compact('professionals'));
    }

    public function agendarCita(Request $request): View
    {
        $selectedSpecialistId = $request->integer('id') ?: null;
        $isFirstBooking = $selectedSpecialistId !== null;
        $scheduleSpecialists = $this->scheduleSpecialists($selectedSpecialistId);

        return view('paciente.agendar-cita', compact('scheduleSpecialists', 'selectedSpecialistId', 'isFirstBooking'));
    }

    public function storeAgendarCita(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'psychologist_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])->where('professional_status', 'approved');
            })],
            'psychologist_name' => ['nullable', 'string', 'max:180'],
            'reason' => ['required', 'string', 'max:180'],
            'modality' => ['required', 'string', 'max:80'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_date_label' => ['nullable', 'string', 'max:80'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'acepta_consentimiento_informado' => ['accepted'],
            'acepta_cancelaciones' => ['accepted'],
        ], [
            'acepta_consentimiento_informado.accepted' => 'Debes aceptar el consentimiento informado para agendar la cita.',
            'acepta_cancelaciones.accepted' => 'Debes aceptar la política de cancelaciones y reembolsos para continuar.',
        ]);

        $professional = $this->resolveProfessional($data['psychologist_id'] ?? null, $data['psychologist_name'] ?? null);
        abort_unless($professional && $professional->hasActiveSubscription(), 422, 'El especialista seleccionado no está disponible para recibir citas.');
        if ($request->input('booking_origin') !== 'directorio') {
            abort_unless($this->hasFollowUpWith($professional->id), 422, 'Solo puedes agendar seguimiento con especialistas que ya tienen una relación clínica contigo. Para una primera cita usa Buscar especialista.');
        }
        $dateValue = $this->normalizeDate($data['appointment_date'] ?? $data['appointment_date_label'] ?? null);
        $time = $data['appointment_time'];
        $startsAt = $dateValue ? $this->parseAppointmentStart($dateValue, $time) : null;

        if ($startsAt && $startsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['appointment_time' => 'Selecciona una fecha y hora futura para la cita.'])->withInput();
        }

        app(AppointmentBusinessRules::class)->validateAppointment(Auth::user(), $professional, $startsAt, $data['modality']);

        $appointment = Appointment::create([
            'patient_id' => Auth::id(),
            'professional_id' => $professional?->id,
            'folio' => 'CITA-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'reason' => $data['reason'],
            'modality' => $data['modality'],
            'appointment_date' => $dateValue,
            'appointment_time' => $time,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt?->copy()->addMinutes(50),
            'notes' => $data['notes'] ?? null,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'amount' => $this->professionalSessionAmount($professional),
            'requested_by' => 'paciente',
        ]);

        return redirect('/paciente/pago-cita?appointment='.$appointment->id)
            ->with('success', 'Solicitud creada. Completa el pago PayPal para enviarla al especialista.');
    }

    public function gestionCitas(): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(patientId: Auth::id());

        $appointments = Auth::user()->patientAppointments()
            ->with('professional.professionalProfile')
            ->latest('starts_at')
            ->get();

        return view('paciente.gestion-citas', compact('appointments'));
    }

    public function storeGestionCitas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'psychologist_id' => ['nullable', 'integer'],
            'psychologist' => ['nullable', 'required_without_all:psychologist_id,modal_psychologist', 'string', 'max:255'],
            'modal_psychologist' => ['nullable', 'required_without_all:psychologist_id,psychologist', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:180'],
            'modality' => ['required', 'string', 'max:80'],
            'date' => ['nullable', 'required_without:modal_date', 'date', 'after_or_equal:today'],
            'modal_date' => ['nullable', 'required_without:date', 'date', 'after_or_equal:today'],
            'time' => ['nullable', 'required_without:modal_time', 'date_format:H:i'],
            'modal_time' => ['nullable', 'required_without:time', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $date = $data['date'] ?? $data['modal_date'];
        $time = $data['time'] ?? $data['modal_time'];
        $startsAt = $this->parseAppointmentStart($date, $time);

        if ($startsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['time' => 'Selecciona una fecha y hora futura para la cita.'])->withInput();
        }

        $professional = $this->resolveProfessional($data['psychologist_id'] ?? null, $data['psychologist'] ?? $data['modal_psychologist'] ?? null);
        abort_unless($professional && $professional->hasActiveSubscription(), 422, 'Selecciona un especialista aprobado con suscripción activa.');
        abort_unless($this->hasFollowUpWith($professional->id), 422, 'Solo puedes solicitar seguimiento con especialistas que ya tienen una relación clínica contigo.');
        app(AppointmentBusinessRules::class)->validateAppointment(Auth::user(), $professional, $startsAt, $data['modality']);

        $appointment = Appointment::create([
            'patient_id' => Auth::id(),
            'professional_id' => $professional->id,
            'folio' => 'CITA-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'reason' => $data['reason'],
            'modality' => $data['modality'],
            'appointment_date' => $date,
            'appointment_time' => $time,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(50),
            'notes' => $data['notes'] ?? null,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'amount' => $this->professionalSessionAmount($professional),
            'requested_by' => 'paciente',
        ]);

        return redirect('/paciente/pago-cita?appointment='.$appointment->id)->with('success', 'Cita creada. Completa el pago PayPal para enviarla al especialista.');
    }

    public function aceptarReagenda(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        abort_unless($appointment->status === 'rescheduled', 422, 'La cita no tiene una propuesta de reagenda pendiente.');
        abort_unless($appointment->reschedule_date && $appointment->reschedule_time, 422, 'La propuesta de reagenda no tiene fecha y hora válidas.');

        $startsAt = $this->parseAppointmentStart($appointment->reschedule_date->toDateString(), $appointment->reschedule_time);

        if ($startsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['appointment' => 'No puedes aceptar una reagenda que ya pasó. Solicita otro horario.']);
        }

        app(AppointmentBusinessRules::class)->validateAppointment(Auth::user(), $appointment->professional, $startsAt, $appointment->modality, $appointment->id);

        $appointment->update([
            'appointment_date' => $appointment->reschedule_date,
            'appointment_time' => $appointment->reschedule_time,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(50),
            'status' => 'accepted',
            'reschedule_proposal' => null,
            'reschedule_date' => null,
            'reschedule_time' => null,
        ]);
        $appointment->refresh();

        try {
            app(AppointmentVideoService::class)->ensureZoomMeeting($appointment);
            $appointment->refresh();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['zoom' => 'La reagenda fue aceptada, pero no se pudo crear la videollamada de Zoom: '.$exception->getMessage()]);
        }

        \App\Support\SafeNotifier::notify($appointment->professional, new AppointmentStatusUpdated($appointment, 'accepted'));
        ClinicalAudit::log('appointment.reschedule.accepted', Auth::id(), $appointment, 'Paciente aceptó propuesta de reagenda.');
        return back()->with('success', 'Propuesta de reagenda aceptada.');
    }

    public function aceptarSolicitudProfesional(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        abort_unless($appointment->requested_by === 'profesional' && $appointment->status === 'pending', 422, 'Esta solicitud no está pendiente de tu confirmación.');

        $startsAt = $this->appointmentStartForValidation($appointment);
        if ($startsAt && $startsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['appointment' => 'No puedes aceptar una cita que ya pasó. Pide al especialista que proponga un nuevo horario.']);
        }
        if ($startsAt) {
            app(AppointmentBusinessRules::class)->validateAppointment(Auth::user(), $appointment->professional, $startsAt, $appointment->modality, $appointment->id);
        }

        $appointment->update([
            'status' => 'accepted',
            'payment_status' => $appointment->payment_status ?: 'waived',
        ]);
        $appointment->refresh();

        try {
            app(AppointmentVideoService::class)->ensureZoomMeeting($appointment);
            $appointment->refresh();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['zoom' => 'La cita fue aceptada, pero no se pudo crear la videollamada de Zoom: '.$exception->getMessage()]);
        }

        \App\Support\SafeNotifier::notify($appointment->professional, new AppointmentStatusUpdated($appointment->fresh(), 'accepted'));
        ClinicalAudit::log('appointment.accepted_by_patient', Auth::id(), $appointment, 'Paciente aceptó solicitud de cita creada por el profesional.');
        return back()->with('success', 'Cita aceptada. Ya aparece en tus próximas sesiones.');
    }

    public function solicitarReagenda(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        abort_if(in_array($appointment->status, ['completed', 'cancelled', 'rejected', 'missed'], true), 422, 'Esta cita ya no puede reagendarse.');
        abort_if($appointment->payment_status === 'pending' && $appointment->status === 'pending_payment', 422, 'Completa el pago antes de solicitar una reagenda.');

        $data = $request->validate([
            'reschedule_date' => ['required', 'date', 'after_or_equal:today'],
            'reschedule_time' => ['required', 'date_format:H:i'],
            'reschedule_proposal' => ['nullable', 'string', 'max:1000'],
        ]);

        $rescheduleStartsAt = $this->parseAppointmentStart($data['reschedule_date'], $data['reschedule_time']);
        if ($rescheduleStartsAt->lt($this->nowInAppTimezone())) {
            return back()->withErrors(['reschedule_time' => 'Selecciona una fecha y hora futura para reagendar.'])->withInput();
        }
        app(AppointmentBusinessRules::class)->validateAppointment(Auth::user(), $appointment->professional, $rescheduleStartsAt, $appointment->modality, $appointment->id);

        $appointment->update([
            'status' => 'pending',
            'requested_by' => 'paciente',
            'reschedule_date' => $data['reschedule_date'],
            'reschedule_time' => $data['reschedule_time'],
            'reschedule_proposal' => $data['reschedule_proposal'] ?? 'El paciente solicita reagendar esta cita.',
        ]);

        \App\Support\SafeNotifier::notify($appointment->professional, new AppointmentStatusUpdated($appointment->fresh(), 'reschedule_requested'));
        ClinicalAudit::log('appointment.reschedule.requested_by_patient', Auth::id(), $appointment, 'Paciente solicitó reagendar una cita.');
        return back()->with('success', 'Solicitud de reagenda enviada al especialista.');
    }

    public function cancelarCita(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        abort_if(in_array($appointment->status, ['completed', 'cancelled', 'missed'], true), 422, 'La cita ya no puede cancelarse desde este panel.');

        $data = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:1000']]);
        $appointment->update([
            'status' => 'cancelled',
            'cancel_reason' => $data['cancel_reason'] ?? 'Cancelada por el paciente.',
        ]);

        \App\Support\SafeNotifier::notify($appointment->professional, new AppointmentStatusUpdated($appointment, 'cancelled'));
        ClinicalAudit::log('appointment.cancelled_by_patient', Auth::id(), $appointment, 'Paciente canceló cita.');
        return back()->with('success', 'Cita cancelada.');
    }

    public function pagoCita(Request $request): View
    {
        $appointment = $request->filled('appointment')
            ? Appointment::where('patient_id', Auth::id())->find($request->integer('appointment'))
            : Auth::user()->patientAppointments()->latest()->first();

        $appointmentPayload = $appointment ? [
            'id' => $appointment->id,
            'specialist' => $appointment->professional?->nombre_completo ?? 'Especialista IRIS',
            'reason' => $appointment->reason,
            'date' => optional($appointment->appointment_date)->format('d/m/Y'),
            'time' => $appointment->appointment_time,
            'modality' => $appointment->modality,
            'amount' => $appointment->amount,
        ] : null;

        return view('paciente.pago-cita', ['appointment' => $appointmentPayload, 'appointmentModel' => $appointment]);
    }

    public function storePagoCita(Request $request): RedirectResponse
    {
        $appointmentId = $request->integer('appointment_id') ?: $request->integer('appointment') ?: $request->integer('id');
        $appointment = $appointmentId
            ? Auth::user()->patientAppointments()->whereKey($appointmentId)->firstOrFail()
            : Auth::user()->patientAppointments()->latest()->firstOrFail();

        return redirect('/paciente/pago-cita?appointment='.$appointment->id);
    }

    public function diario(): View
    {
        $diaryEntries = $this->patientDiaryEntries();
        $diaryProfessionals = $this->diaryAuthorizationProfessionals();

        return view('paciente.diario-paciente', compact('diaryEntries', 'diaryProfessionals'));
    }


    public function diarioTodas(): View
    {
        $diaryEntries = $this->patientDiaryEntries();
        $diaryProfessionals = $this->diaryAuthorizationProfessionals();

        return view('paciente.diario-todas', compact('diaryEntries', 'diaryProfessionals'));
    }

    public function storeDiario(Request $request): RedirectResponse
    {
        $allowedProfessionals = $this->diaryAuthorizationProfessionals()->pluck('id')->all();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:8000'],
            'entry' => ['nullable', 'string', 'max:8000'],
            'mood' => ['nullable', 'string', 'max:80'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'authorized_professional_id' => ['nullable', Rule::in($allowedProfessionals)],
        ]);

        $timezone = $this->appTimezone();
        $now = now($timezone);
        $entryDate = $now->toDateString();
        $content = trim((string) ($data['content'] ?? $data['entry'] ?? ''));
        abort_if($content === '', 422, 'Escribe el contenido de tu nota antes de guardarla.');

        $entry = DiaryEntry::firstOrNew([
            'patient_id' => Auth::id(),
            'entry_date' => $entryDate,
        ]);

        $notes = $entry->exists ? ($entry->notes ?? []) : [];
        $notes[] = [
            'time' => $now->format('H:i'),
            'title' => $data['title'] ?? null,
            'content' => $content,
            'mood' => $data['mood'] ?? null,
            'emoji' => $data['emoji'] ?? null,
            'saved_at' => $now->toDateTimeString(),
        ];

        $authorizedProfessionalId = $data['authorized_professional_id'] ?? $entry->authorized_professional_id;

        $entry->fill([
            'title' => 'Diario del '.$now->format('d/m/Y'),
            'content' => $this->composeDailyDiaryContent($notes),
            'notes' => $notes,
            'mood' => $data['mood'] ?? $entry->mood,
            'emoji' => $data['emoji'] ?? $entry->emoji,
            'authorized_professional_id' => $authorizedProfessionalId,
            'authorized_at' => $authorizedProfessionalId ? now() : null,
        ])->save();

        ClinicalAudit::log('diary.daily_note.saved', Auth::id(), $entry, 'Paciente agregó nota al registro diario agrupado por fecha.', [
            'entry_date' => $entryDate,
            'note_time' => $now->format('H:i'),
            'authorized_professional_id' => $authorizedProfessionalId,
        ]);

        return back()->with('success', 'Nota guardada en el registro de hoy. Después de las 12:00 AM se creará un nuevo día automáticamente.');
    }

    public function misTareas(): View
    {
        $tasks = Auth::user()
            ->tasksAssigned()
            ->with('professional.professionalProfile')
            ->latest('due_date')
            ->latest()
            ->get();

        $pendingTasks = $tasks->filter(fn (PatientTask $task) => in_array($task->status, ['pendiente', 'requiere_cambios'], true))->values();
        $completedTasks = $tasks->filter(fn (PatientTask $task) => in_array($task->status, ['entregada', 'completada'], true))->values();

        return view('paciente.mis-tareas', compact('tasks', 'pendingTasks', 'completedTasks'));
    }


    public function completeTask(Request $request, PatientTask $task): RedirectResponse
    {
        abort_unless($task->patient_id === Auth::id(), 403);
        abort_if($task->isApproved(), 403, 'La tarea ya fue revisada y aprobada.');

        $data = $request->validate([
            'follow_up' => ['nullable', 'string', 'max:5000', 'required_without:evidence_pdf'],
            'evidence_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240', 'required_without:follow_up'],
        ]);

        $filePath = $task->evidence_file_path;
        $fileName = $task->evidence_file_name;
        $fileDisk = $task->evidence_file_disk ?: ($task->evidence_file_path ? 'local' : null);
        $fileMime = $task->evidence_file_mime;
        $fileSize = $task->evidence_file_size;

        if ($request->hasFile('evidence_pdf')) {
            $this->deleteTaskEvidenceFile($task);

            $file = $request->file('evidence_pdf');
            $filePath = $file->storeAs(
                'task-evidence/'.Auth::id(),
                Str::uuid().'.pdf',
                'local'
            );
            $fileName = $file->getClientOriginalName();
            $fileDisk = 'local';
            $fileMime = $file->getMimeType() ?: 'application/pdf';
            $fileSize = $file->getSize();
        }

        $task->update([
            'status' => 'entregada',
            'evidence' => $data['follow_up'] ?? null,
            'follow_up' => $data['follow_up'] ?? null,
            'evidence_file_path' => $filePath,
            'evidence_file_name' => $fileName,
            'evidence_file_disk' => $fileDisk,
            'evidence_file_mime' => $fileMime,
            'evidence_file_size' => $fileSize,
            'submitted_at' => now(),
            'review_status' => 'pendiente_revision',
            'review_feedback' => null,
            'reviewed_at' => null,
            'completed_at' => null,
        ]);

        if ($task->professional) {
            \App\Support\SafeNotifier::notify($task->professional, new \App\Notifications\TaskSubmitted($task->fresh()));
        }

        ClinicalAudit::log('task.submitted', Auth::id(), $task, 'Paciente entregó tarea terapéutica para revisión.');
        return back()->with('success', 'Tarea entregada. Tu especialista ya puede revisarla.');
    }

    public function unsubmitTask(PatientTask $task): RedirectResponse
    {
        abort_unless($task->patient_id === Auth::id(), 403);
        abort_unless($task->canBeUnsubmitted(), 403, 'Esta tarea ya no se puede desentregar.');

        $task->update([
            'status' => 'pendiente',
            'review_status' => 'pendiente',
            'submitted_at' => null,
            'review_feedback' => null,
            'reviewed_at' => null,
            'completed_at' => null,
        ]);

        ClinicalAudit::log('task.unsubmitted', Auth::id(), $task, 'Paciente desentregó tarea terapéutica para modificar respuesta.');
        return back()->with('success', 'Tarea desentregada. Puedes modificar tu respuesta y volver a completarla.');
    }

    public function viewTaskPdf(PatientTask $task)
    {
        abort_unless($task->patient_id === Auth::id(), 403);

        ClinicalAudit::log('task.pdf.viewed_by_patient', $task->patient_id, $task, 'Paciente visualizó PDF adjunto a tarea.');

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

    private function deleteTaskEvidenceFile(PatientTask $task): void
    {
        if (! $task->evidence_file_path) {
            return;
        }

        $path = $task->evidence_file_path;
        $disk = $task->evidence_file_disk ?: 'local';

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        } elseif (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        } elseif (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function safePdfFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9_. -]/', '_', $fileName) ?: 'evidencia-tarea.pdf';

        return Str::endsWith(Str::lower($fileName), '.pdf') ? $fileName : $fileName.'.pdf';
    }

    public function perfil(): View
    {
        $user = Auth::user()->load('emergencyContact', 'patientProfile');
        return view('paciente.perfil-paciente', compact('user'));
    }

    public function updatePerfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'genero' => ['nullable', 'string', 'max:60'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'emergencia_nombre' => ['nullable', 'string', 'max:180'],
            'emergencia_relacion' => ['nullable', 'string', 'max:80'],
            'emergencia_telefono' => ['nullable', 'string', 'max:30'],
            'terapia_previa' => ['nullable', 'string', 'max:80'],
            'medicacion_actual' => ['nullable', 'string', 'max:120'],
            'motivo_consulta' => ['nullable', 'string', 'max:3000'],
            'objetivos' => ['nullable', 'string', 'max:3000'],
            'ocupacion' => ['nullable', 'string', 'max:180'],
            'domicilio' => ['nullable', 'string', 'max:3000'],
            'estado_civil' => ['nullable', 'string', 'max:80'],
            'antecedentes' => ['nullable', 'string', 'max:3000'],
            'alergias' => ['nullable', 'string', 'max:3000'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Auth::user();
        $userPayload = array_filter($data, fn ($key) => in_array($key, ['nombre', 'apellidos', 'telefono', 'genero', 'fecha_nacimiento'], true), ARRAY_FILTER_USE_KEY);
        if (! empty($data['password'])) {
            $userPayload['password'] = Hash::make($data['password']);
        }
        $userPayload['profile_completed'] = true;
        $user->update($userPayload);

        $user->emergencyContact()->updateOrCreate(['user_id' => $user->id], [
            'nombre' => $data['emergencia_nombre'] ?? $user->emergencyContact?->nombre ?? '',
            'relacion' => $data['emergencia_relacion'] ?? $user->emergencyContact?->relacion ?? '',
            'telefono' => $data['emergencia_telefono'] ?? $user->emergencyContact?->telefono ?? '',
        ]);
        $user->patientProfile()->updateOrCreate(['user_id' => $user->id], [
            'terapia_previa' => $data['terapia_previa'] ?? null,
            'medicacion_actual' => $data['medicacion_actual'] ?? null,
            'motivo_consulta' => $data['motivo_consulta'] ?? null,
            'objetivos' => $data['objetivos'] ?? null,
            'ocupacion' => $data['ocupacion'] ?? null,
            'domicilio' => $data['domicilio'] ?? null,
            'estado_civil' => $data['estado_civil'] ?? null,
            'antecedentes' => $data['antecedentes'] ?? null,
            'alergias' => $data['alergias'] ?? null,
        ]);

        ClinicalAudit::log('patient.profile.updated', $user->id, $user->patientProfile, 'Paciente actualizó perfil clínico.');
        return back()->with('success', 'Perfil actualizado.');
    }

    public function auxilio(AuxilioSessionService $auxilio): View
    {
        $activeAuxilio = $auxilio->activeAuxilioForPatient(Auth::user());

        return view('paciente.auxilio-paciente', compact('activeAuxilio'));
    }

    public function solicitarAuxilioZoom(AuxilioSessionService $auxilio): RedirectResponse
    {
        try {
            $appointment = $auxilio->connectPatient(Auth::user());
        } catch (RuntimeException $exception) {
            return back()->with('warning', $exception->getMessage());
        }

        return redirect()
            ->route('paciente.auxilio')
            ->with('success', 'Profesional en Modo Escucha conectado. Se está preparando la videollamada.')
            ->with('auto_open_call', true);
    }

    public function sesion(Request $request): View
    {
        app(AppointmentLifecycleService::class)->markExpiredAcceptedAsMissed(patientId: Auth::id());

        $appointment = null;
        if ($request->filled('appointment')) {
            $appointment = Auth::user()->patientAppointments()
                ->with('professional.professionalProfile')
                ->whereKey($request->integer('appointment'))
                ->where('status', 'accepted')
                ->first();

            if ($appointment && ! $appointment->is_video_session_available) {
                $appointment = null;
            }
        }

        return view('paciente.sesion', compact('appointment'));
    }


    private function patientDiaryEntries()
    {
        return Auth::user()->diaryEntries()
            ->with('authorizedProfessional.professionalProfile')
            ->latest('entry_date')
            ->get()
            ->map(fn (DiaryEntry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'content' => $entry->content,
                'notes' => $entry->notes ?? [],
                'mood' => $entry->mood,
                'emoji' => $entry->emoji,
                'date' => optional($entry->entry_date)->format('Y-m-d'),
                'authorized_professional' => $entry->authorizedProfessional?->nombre_completo,
                'authorized_professional_id' => $entry->authorized_professional_id,
            ]);
    }

    private function diaryAuthorizationProfessionals()
    {
        $professionalIds = Auth::user()->patientAppointments()
            ->whereNotNull('professional_id')
            ->whereIn('status', ['accepted', 'completed', 'missed', 'rescheduled'])
            ->pluck('professional_id')
            ->unique()
            ->values();

        if ($professionalIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $professionalIds)
            ->whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])
            ->where('professional_status', 'approved')
            ->orderBy('nombre')
            ->get();
    }

    private function composeDailyDiaryContent(array $notes): string
    {
        return collect($notes)->map(function (array $note) {
            $title = filled($note['title'] ?? null) ? trim($note['title']).': ' : '';
            $mood = filled($note['mood'] ?? null) ? ' · Estado: '.trim($note['mood']) : '';
            $emoji = filled($note['emoji'] ?? null) ? ' '.$note['emoji'] : '';
            return '['.($note['time'] ?? '--:--').']'.$emoji.$mood."\n".$title.trim((string) ($note['content'] ?? ''));
        })->implode("\n\n");
    }

    private function scheduleSpecialists(?int $selectedSpecialistId = null): array
    {
        $query = $this->approvedActiveProfessionalsQuery()->with('professionalProfile');

        if ($selectedSpecialistId) {
            $profiles = $query->whereKey($selectedSpecialistId)->get();
        } else {
            $assignedIds = Auth::user()->patientAppointments()
                ->whereNotNull('professional_id')
                ->whereIn('status', ['pending', 'accepted', 'rescheduled', 'completed', 'missed'])
                ->whereIn('payment_status', ['paid', 'waived'])
                ->pluck('professional_id')
                ->unique()
                ->values();

            $profiles = $assignedIds->isEmpty()
                ? collect()
                : $query->whereIn('id', $assignedIds)->get();
        }

        return $profiles->mapWithKeys(function (User $user) {
            $profile = $user->professionalProfile;
            $slug = Str::slug($user->nombre_completo ?: $user->email);
            return [$slug => [
                'id' => $user->id,
                'slug' => $slug,
                'name' => $user->nombre_completo,
                'role' => $user->rol,
                'specialty' => $profile?->especialidad_principal ?? ucfirst($user->rol),
                'experience' => $profile?->experiencia_anios ? $profile->experiencia_anios.' años de experiencia' : 'Experiencia pendiente de especificar',
                'modes' => $profile?->modalidad === 'presencial' ? ['Presencial'] : ['Videollamada', 'Llamada'],
                'price' => '$'.number_format($this->professionalSessionAmount($user), 0).' MXN',
                'nextSlot' => $profile?->proximo_espacio ?: 'Selecciona una fecha y horario disponibles',
                'days' => $profile?->dias_atencion ?? [],
                'availability' => $profile?->disponibilidad ?? [],
                'duration' => $profile?->duracion_sesion ?: 50,
                'dates' => [],
            ]];
        })->toArray();
    }

    private function hasFollowUpWith(int $professionalId): bool
    {
        return Auth::user()->patientAppointments()
            ->where('professional_id', $professionalId)
            ->whereIn('status', ['pending', 'accepted', 'rescheduled', 'completed', 'missed'])
            ->whereIn('payment_status', ['paid', 'waived'])
            ->exists();
    }

    private function resolveProfessional(mixed $id = null, ?string $name = null): ?User
    {
        $query = $this->approvedActiveProfessionalsQuery()->with('professionalProfile', 'subscriptions');

        if ($id && is_numeric($id)) {
            return (clone $query)->whereKey((int) $id)->first();
        }

        if ($name) {
            return (clone $query)->where(function ($query) use ($name) {
                $query->where('nombre', 'like', '%'.$name.'%')
                    ->orWhere('apellidos', 'like', '%'.$name.'%')
                    ->orWhere('name', 'like', '%'.$name.'%');
            })->first();
        }

        return null;
    }

    private function approvedActiveProfessionalsQuery()
    {
        return User::query()
            ->whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])
            ->where('professional_status', 'approved')
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    });
            });
    }

    private function professionalSessionAmount(User $professional): float
    {
        $amount = (float) ($professional->professionalProfile?->costo_min ?? 0);
        abort_if($amount <= 0, 422, 'El especialista seleccionado no tiene una tarifa configurada.');
        return $amount;
    }

    private function normalizeDate(?string $raw): ?string
    {
        if (! $raw) return null;
        try { return Carbon::parse($raw, $this->appTimezone())->toDateString(); } catch (\Throwable) { return $this->nowInAppTimezone()->addDay()->toDateString(); }
    }

    private function parseAppointmentStart(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.$time, $this->appTimezone());
    }

    private function appointmentStartForValidation(Appointment $appointment): ?Carbon
    {
        if ($appointment->appointment_date && $appointment->appointment_time) {
            return $this->parseAppointmentStart($appointment->appointment_date->toDateString(), $appointment->appointment_time);
        }

        return $appointment->starts_at?->copy()->timezone($this->appTimezone());
    }

    private function nowInAppTimezone(): Carbon
    {
        return now($this->appTimezone());
    }

    private function appTimezone(): string
    {
        return (string) config('app.timezone', 'America/Mexico_City');
    }
}
