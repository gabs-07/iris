<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppointmentVideoService
{
    public function __construct(private ZoomClient $zoom) {}

    public function shouldCreateZoomMeeting(Appointment $appointment): bool
    {
        $modality = Str::of((string) $appointment->modality)->lower();

        return $modality->contains(['video', 'virtual', 'online', 'en linea', 'en línea', 'zoom']);
    }

    public function ensureZoomMeeting(Appointment $appointment, bool $force = false): ?Appointment
    {
        if (! $this->shouldCreateZoomMeeting($appointment)) {
            return null;
        }

        if (! $force && filled($appointment->room_link)) {
            return null;
        }

        $meeting = $this->zoom->createMeetingForAppointment($appointment->loadMissing(['patient', 'professional']));

        $appointment->forceFill([
            'zoom_meeting_id' => (string) data_get($meeting, 'id'),
            'zoom_join_url' => data_get($meeting, 'join_url'),
            'zoom_start_url' => data_get($meeting, 'start_url'),
            'zoom_password' => data_get($meeting, 'password'),
            'zoom_created_at' => now(),
            'zoom_payload' => $meeting,
            'room_link' => data_get($meeting, 'join_url') ?: $appointment->room_link,
        ])->save();

        Log::info('Zoom meeting created for IRIS appointment', [
            'appointment_id' => $appointment->id,
            'zoom_meeting_id' => $appointment->zoom_meeting_id,
        ]);

        return $appointment->fresh(['patient', 'professional']);
    }
}
