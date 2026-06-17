<?php

namespace App\Services;

use App\Models\Appointment;
use App\Support\ClinicalAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class AppointmentLifecycleService
{
    public const SESSION_EARLY_ACCESS_MINUTES = 2;
    public const SESSION_ACCESS_MINUTES = 60;

    public function markExpiredAcceptedAsMissed(?int $patientId = null, ?int $professionalId = null): int
    {
        if (! Schema::hasColumn('appointments', 'missed_at')) {
            return 0;
        }

        $now = Carbon::now(config('app.timezone'));
        $query = Appointment::query()
            ->where('status', 'accepted')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $now->copy()->subMinutes(self::SESSION_ACCESS_MINUTES));

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }

        if ($professionalId !== null) {
            $query->where('professional_id', $professionalId);
        }

        $appointments = $query->get();
        $updated = 0;

        foreach ($appointments as $appointment) {
            try {
                $appointment->forceFill([
                    'status' => 'missed',
                    'missed_at' => $now,
                    'cancel_reason' => $appointment->cancel_reason ?: 'La sesión superó la ventana de acceso de 1 hora sin cerrarse como completada.',
                ])->save();

                ClinicalAudit::log('appointment.missed', $appointment->patient_id, $appointment, 'Cita marcada automáticamente como perdida después de 1 hora de la hora programada.');
                $updated++;
            } catch (QueryException) {
                // Si el proyecto aún no ejecutó la migración que agrega el estado "missed",
                // no rompemos la pantalla; las vistas siguen mostrando el estado efectivo como perdida.
                return $updated;
            }
        }

        return $updated;
    }
}
