<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentStatusUpdated;
use App\Support\ClinicalAudit;
use App\Support\SafeNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AuxilioSessionService
{
    private const PRIORITY_AUXILIO_EMAIL = 'doctor-iris@reembolsosgi.com';

    public function __construct(private AppointmentVideoService $videoService) {}

    public function connectPatient(User $patient): Appointment
    {
        if (! $patient->isPaciente()) {
            throw new RuntimeException('Solo los pacientes pueden solicitar auxilio desde este flujo.');
        }

        if ($active = $this->activeAuxilioForPatient($patient)) {
            return $active;
        }

        $professional = $this->findAvailableProfessional();
        if (! $professional) {
            throw new RuntimeException('Por el momento no hay psicólogos en Modo Escucha. Intenta nuevamente en unos minutos o llama a emergencias si hay riesgo inmediato.');
        }

        $timezone = config('app.timezone', 'America/Mexico_City');
        $startsAt = Carbon::now($timezone);
        $endsAt = $startsAt->copy()->addMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES);

        $appointment = DB::transaction(function () use ($patient, $professional, $startsAt, $endsAt) {
            return Appointment::create([
                'patient_id' => $patient->id,
                'professional_id' => $professional->id,
                'folio' => 'AUX-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'reason' => 'Auxilio emocional inmediato',
                'modality' => 'Videollamada',
                'appointment_date' => $startsAt->toDateString(),
                'appointment_time' => $startsAt->format('H:i'),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => 'Solicitud generada desde el botón de Auxilio. No sustituye servicios de emergencia.',
                'status' => 'accepted',
                'payment_status' => 'waived',
                'amount' => 0,
                'requested_by' => 'auxilio',
            ]);
        });

        $appointment->load(['patient', 'professional.professionalProfile']);

        try {
            $this->videoService->ensureZoomMeeting($appointment, true);
        } catch (RuntimeException $exception) {
            $appointment->delete();
            throw $exception;
        }

        $appointment = $appointment->fresh(['patient', 'professional.professionalProfile']);

        SafeNotifier::notify($professional, new AppointmentStatusUpdated($appointment, 'auxilio'));
        ClinicalAudit::log('auxilio.zoom.created', $patient->id, $appointment, 'Paciente solicitó auxilio y se creó una reunión Zoom con un profesional en Modo Escucha.');

        return $appointment;
    }



    public function connectGuest(?string $guestName = null, ?string $guestContact = null): Appointment
    {
        $professional = $this->findAvailableProfessional();
        if (! $professional) {
            throw new RuntimeException('Por el momento no hay psicólogos en Modo Escucha. Intenta nuevamente en unos minutos o llama a emergencias si hay riesgo inmediato.');
        }

        $timezone = config('app.timezone', 'America/Mexico_City');
        $startsAt = Carbon::now($timezone);
        $endsAt = $startsAt->copy()->addMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES);
        $displayName = trim((string) $guestName) ?: 'Invitado Auxilio';

        $appointment = DB::transaction(function () use ($displayName, $guestContact, $professional, $startsAt, $endsAt) {
            $guest = User::create([
                'nombre' => $displayName,
                'apellidos' => '',
                'name' => $displayName,
                'email' => 'invitado+'.(string) Str::uuid().'@iris.local',
                'password' => Hash::make(Str::random(48)),
                'rol' => 'invitado',
                'telefono' => $guestContact,
                'profile_completed' => true,
                'professional_status' => 'none',
                'email_verified_at' => now(),
            ]);

            return Appointment::create([
                'patient_id' => $guest->id,
                'professional_id' => $professional->id,
                'folio' => 'AUX-GUEST-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'reason' => 'Auxilio emocional inmediato para invitado',
                'modality' => 'Videollamada',
                'appointment_date' => $startsAt->toDateString(),
                'appointment_time' => $startsAt->format('H:i'),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => 'Solicitud generada por usuario invitado. Al finalizar se invita al registro para seguimiento.',
                'status' => 'accepted',
                'payment_status' => 'waived',
                'amount' => 0,
                'requested_by' => 'auxilio_invitado',
            ]);
        });

        $appointment->load(['patient', 'professional.professionalProfile']);

        try {
            $this->videoService->ensureZoomMeeting($appointment, true);
        } catch (RuntimeException $exception) {
            $appointment->patient?->delete();
            $appointment->delete();
            throw $exception;
        }

        $appointment = $appointment->fresh(['patient', 'professional.professionalProfile']);

        SafeNotifier::notify($professional, new AppointmentStatusUpdated($appointment, 'auxilio_invitado'));
        ClinicalAudit::log('auxilio.guest.zoom.created', $appointment->patient_id, $appointment, 'Usuario invitado solicitó auxilio y se creó una reunión Zoom con un profesional en Modo Escucha.');

        return $appointment;
    }

    public function activeAuxilioForPatient(User $patient): ?Appointment
    {
        $now = Carbon::now(config('app.timezone', 'America/Mexico_City'));

        return $patient->patientAppointments()
            ->with(['professional.professionalProfile'])
            ->where('requested_by', 'auxilio')
            ->where('status', 'accepted')
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $now->copy()->subMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES))
            ->latest('starts_at')
            ->first();
    }

    private function findAvailableProfessional(): ?User
    {
        $now = Carbon::now(config('app.timezone', 'America/Mexico_City'));

        return User::query()
            ->whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])
            ->where('profile_completed', true)
            ->where('professional_status', 'approved')
            ->whereHas('professionalProfile', function ($query) {
                $query->where('modo_escucha_activo', true);
            })
            ->whereHas('subscriptions', function ($query) use ($now) {
                $query->where('status', 'active')
                    ->where(function ($subscription) use ($now) {
                        $subscription->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                    });
            })
            ->with(['professionalProfile'])
            ->withCount(['professionalAppointments as active_auxilio_count' => function ($query) use ($now) {
                $query->whereIn('requested_by', ['auxilio', 'auxilio_invitado'])
                    ->where('status', 'accepted')
                    ->where('starts_at', '>=', $now->copy()->subMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES));
            }])
            ->orderByRaw('CASE WHEN email = ? THEN 0 ELSE 1 END', [self::PRIORITY_AUXILIO_EMAIL])
            ->orderBy('active_auxilio_count')
            ->orderBy('professional_approved_at')
            ->first();
    }
}
