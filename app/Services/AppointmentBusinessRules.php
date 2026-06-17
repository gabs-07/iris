<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentBusinessRules
{
    private const ACTIVE_STATUSES = ['pending_payment', 'pending', 'accepted', 'rescheduled'];
    private const ALLOWED_DAYS = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

    public function validateProfessionalProfileSchedule(array $data): void
    {
        $days = array_values(array_filter((array) ($data['dias_atencion'] ?? [])));
        foreach ($days as $day) {
            if (! in_array($day, self::ALLOWED_DAYS, true)) {
                throw ValidationException::withMessages(['dias_atencion' => 'Selecciona únicamente días de atención válidos.']);
            }
        }

        $min = $data['costo_min'] ?? null;
        $max = $data['costo_max'] ?? null;
        if ($min !== null && $max !== null && $max !== '' && (float) $max < (float) $min) {
            throw ValidationException::withMessages(['costo_max' => 'La tarifa máxima no puede ser menor que la tarifa por sesión.']);
        }

        $availability = (array) ($data['disponibilidad'] ?? []);
        foreach ($days as $day) {
            $start = $availability[$day]['inicio'] ?? null;
            $end = $availability[$day]['fin'] ?? null;

            if (($start && ! $end) || (! $start && $end)) {
                throw ValidationException::withMessages(["disponibilidad.$day" => 'Captura hora de inicio y fin para cada día seleccionado.']);
            }

            if ($start && $end) {
                $startTime = $this->normalizeTime($start);
                $endTime = $this->normalizeTime($end);
                if (! $startTime || ! $endTime || $startTime >= $endTime) {
                    throw ValidationException::withMessages(["disponibilidad.$day" => 'La hora final debe ser mayor que la hora inicial.']);
                }
            }
        }
    }

    public function validateAppointment(User $patient, User $professional, Carbon $startsAt, string $modality, ?int $ignoreAppointmentId = null): void
    {
        $this->validateProfessionalCanReceiveAppointments($professional);
        $this->validateModality($professional, $modality);
        $this->validateWithinProfessionalAvailability($professional, $startsAt);
        $this->validateNoOverlaps($patient, $professional, $startsAt, $ignoreAppointmentId);
    }

    public function validateProfessionalCanReceiveAppointments(User $professional): void
    {
        if (! $professional->isProfesional() || $professional->professional_status !== 'approved' || ! $professional->hasActiveSubscription()) {
            throw ValidationException::withMessages(['psychologist_id' => 'El especialista no está aprobado o no tiene suscripción activa.']);
        }
    }

    public function validateModality(User $professional, string $requestedModality): void
    {
        $profileMode = mb_strtolower((string) ($professional->professionalProfile?->modalidad ?? 'ambas'));
        $requested = mb_strtolower(trim($requestedModality));

        $isRemote = str_contains($requested, 'video') || str_contains($requested, 'llamada') || str_contains($requested, 'online') || str_contains($requested, 'línea') || str_contains($requested, 'linea');
        $isInPerson = str_contains($requested, 'presencial');

        if ($profileMode === 'presencial' && $isRemote) {
            throw ValidationException::withMessages(['modality' => 'Este profesional solo atiende en modalidad presencial.']);
        }

        if (in_array($profileMode, ['online', 'videollamada'], true) && $isInPerson) {
            throw ValidationException::withMessages(['modality' => 'Este profesional solo atiende en modalidad en línea/videollamada.']);
        }
    }

    public function validateWithinProfessionalAvailability(User $professional, Carbon $startsAt): void
    {
        $profile = $professional->professionalProfile;
        if (! $profile) {
            throw ValidationException::withMessages(['appointment_time' => 'El profesional no tiene perfil de disponibilidad configurado.']);
        }

        $day = $this->spanishDayName($startsAt);
        $days = array_values(array_filter((array) ($profile->dias_atencion ?? [])));
        if (! empty($days) && ! in_array($day, $days, true)) {
            throw ValidationException::withMessages(['appointment_date' => 'El profesional no atiende ese día. Selecciona uno de sus días disponibles.']);
        }

        $ranges = $this->availabilityRangesForDay($profile->disponibilidad ?? [], $day);
        if (empty($ranges)) {
            return;
        }

        $duration = (int) ($profile->duracion_sesion ?: 50);
        $endAt = $startsAt->copy()->addMinutes($duration);
        $slotStart = $startsAt->format('H:i');
        $slotEnd = $endAt->format('H:i');

        foreach ($ranges as $range) {
            if ($slotStart >= $range['inicio'] && $slotEnd <= $range['fin']) {
                return;
            }
        }

        $readableRanges = collect($ranges)->map(fn ($range) => $range['inicio'].'-'.$range['fin'])->implode(', ');
        throw ValidationException::withMessages(['appointment_time' => 'La hora elegida está fuera del horario de servicio del profesional para '.$day.' ('.$readableRanges.').']);
    }

    public function validateNoOverlaps(User $patient, User $professional, Carbon $startsAt, ?int $ignoreAppointmentId = null): void
    {
        $duration = (int) ($professional->professionalProfile?->duracion_sesion ?: 50);
        $endsAt = $startsAt->copy()->addMinutes($duration);

        $overlapQuery = Appointment::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('starts_at')
            ->where(function ($query) use ($patient, $professional) {
                $query->where('professional_id', $professional->id)
                    ->orWhere('patient_id', $patient->id);
            })
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            });

        if ($ignoreAppointmentId) {
            $overlapQuery->whereKeyNot($ignoreAppointmentId);
        }

        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages(['appointment_time' => 'Ese horario se cruza con otra cita activa del paciente o del profesional. Selecciona otro horario.']);
        }
    }

    public function normalizedAvailability(array $availability, array $days): array
    {
        $normalized = $availability;
        foreach (self::ALLOWED_DAYS as $day) {
            $start = $availability[$day]['inicio'] ?? null;
            $end = $availability[$day]['fin'] ?? null;
            if ($start || $end) {
                $normalized[$day] = ['inicio' => $this->normalizeTime($start), 'fin' => $this->normalizeTime($end)];
            }
        }

        return $normalized;
    }

    private function availabilityRangesForDay(array $availability, string $day): array
    {
        $raw = $availability[$day] ?? null;
        if (! $raw && isset($availability['general'])) {
            return $this->parseGeneralAvailability((string) $availability['general']);
        }

        if (! $raw) {
            return [];
        }

        if (is_array($raw) && isset($raw['inicio'], $raw['fin'])) {
            return [[
                'inicio' => $this->normalizeTime((string) $raw['inicio']),
                'fin' => $this->normalizeTime((string) $raw['fin']),
            ]];
        }

        if (is_array($raw) && array_is_list($raw) && count($raw) >= 2 && is_string($raw[0])) {
            return [[
                'inicio' => $this->normalizeTime((string) $raw[0]),
                'fin' => $this->normalizeTime((string) $raw[1]),
            ]];
        }

        if (is_array($raw) && array_is_list($raw)) {
            return collect($raw)->map(function ($range) {
                if (! is_array($range)) {
                    return null;
                }
                $start = $range['inicio'] ?? $range['start'] ?? $range[0] ?? null;
                $end = $range['fin'] ?? $range['end'] ?? $range[1] ?? null;
                return $start && $end ? ['inicio' => $this->normalizeTime($start), 'fin' => $this->normalizeTime($end)] : null;
            })->filter(fn ($range) => $range && $range['inicio'] && $range['fin'])->values()->all();
        }

        return [];
    }

    private function parseGeneralAvailability(string $value): array
    {
        $value = mb_strtolower($value);
        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:-|a|hasta)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/iu', $value, $matches)) {
            $start = $this->normalizeTime(($matches[1] ?? '').':'.($matches[2] ?? '00').($matches[3] ?? ''));
            $end = $this->normalizeTime(($matches[4] ?? '').':'.($matches[5] ?? '00').($matches[6] ?? ''));
            if ($start && $end && $start < $end) {
                return [['inicio' => $start, 'fin' => $end]];
            }
        }
        return [];
    }

    private function normalizeTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $value = trim(mb_strtolower($value));
        if (preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)?$/iu', $value, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) ($matches[2] ?? 0);
            $period = $matches[3] ?? null;
            if ($period === 'pm' && $hour < 12) {
                $hour += 12;
            }
            if ($period === 'am' && $hour === 12) {
                $hour = 0;
            }
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }
        return null;
    }

    private function spanishDayName(Carbon $date): string
    {
        return match ((int) $date->dayOfWeekIso) {
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábado',
            default => 'domingo',
        };
    }
}
