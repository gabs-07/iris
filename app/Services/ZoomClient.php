<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ZoomClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.zoom.account_id'))
            && filled(config('services.zoom.client_id'))
            && filled(config('services.zoom.client_secret'));
    }

    public function createMeetingForAppointment(Appointment $appointment): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Faltan las variables ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID o ZOOM_CLIENT_SECRET en el archivo .env.');
        }

        $token = $this->accessToken();
        $timezone = (string) config('services.zoom.timezone', config('app.timezone', 'America/Mexico_City'));
        $startsAt = $this->appointmentStartsAt($appointment, $timezone);
        $endsAt = $appointment->ends_at?->copy()->timezone($timezone);
        $duration = $startsAt && $endsAt
            ? (int) max(1, round($startsAt->diffInMinutes($endsAt)))
            : (int) config('services.zoom.default_duration', 50);

        $payload = [
            'topic' => Str::limit('IRIS · '.($appointment->patient?->nombre_completo ?? 'Paciente').' con '.($appointment->professional?->nombre_completo ?? 'Especialista').' · '.$appointment->folio, 180, ''),
            'type' => 2,
            'start_time' => $startsAt?->format('Y-m-d\TH:i:s'),
            'duration' => $duration,
            'timezone' => $timezone,
            'password' => Str::upper(Str::random(10)),
            'agenda' => Str::limit((string) ($appointment->reason ?: 'Sesión psicológica IRIS'), 1800, ''),
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'waiting_room' => true,
                'mute_upon_entry' => true,
                'approval_type' => 2,
                'audio' => 'both',
                'auto_recording' => 'none',
            ],
        ];

        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken($token)
            ->post(rtrim((string) config('services.zoom.base_url', 'https://api.zoom.us/v2'), '/').'/users/me/meetings', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Zoom no pudo crear la reunión: '.$this->responseMessage($response->json(), $response->body()));
        }

        return $response->json();
    }


    private function appointmentStartsAt(Appointment $appointment, string $timezone): ?Carbon
    {
        if ($appointment->appointment_date && $appointment->appointment_time) {
            return Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->appointment_time, $timezone);
        }

        return $appointment->starts_at?->copy()->timezone($timezone);
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->timeout(20)
            ->withBasicAuth((string) config('services.zoom.client_id'), (string) config('services.zoom.client_secret'))
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => config('services.zoom.account_id'),
            ]);

        if (! $response->successful() || blank($response->json('access_token'))) {
            throw new RuntimeException('Zoom no devolvió un access token válido: '.$this->responseMessage($response->json(), $response->body()));
        }

        return (string) $response->json('access_token');
    }

    private function responseMessage(mixed $json, string $body): string
    {
        $message = data_get($json, 'message') ?: data_get($json, 'reason') ?: $body;

        return Str::limit((string) $message, 500);
    }
}
