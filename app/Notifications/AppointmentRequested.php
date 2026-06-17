<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentRequested extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array { return ['database', 'mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva solicitud de cita en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Tienes una nueva solicitud de cita de '.$this->appointment->patient?->nombre_completo.'.')
            ->line('Fecha: '.optional($this->appointment->starts_at)->format('d/m/Y H:i'))
            ->action('Responder solicitud', url('/psicologo/solicitudes-psicologo'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva solicitud de cita',
            'message' => ($this->appointment->patient?->nombre_completo ?? 'Un paciente').' solicitó una cita.',
            'url' => '/psicologo/solicitudes-psicologo',
            'appointment_id' => $this->appointment->id,
        ];
    }
}
