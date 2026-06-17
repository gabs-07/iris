<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment, public string $status) {}

    public function via(object $notifiable): array { return ['database', 'mail']; }

    private function label(): string
    {
        return match ($this->status) {
            'accepted' => 'aceptada',
            'rejected' => 'rechazada',
            'rescheduled' => 'con propuesta de reagenda',
            'paid' => 'pagada',
            'cancelled' => 'cancelada',
            'completed' => 'completada',
            'missed' => 'marcada como perdida / sin entrar',
            'refunded' => 'reembolsada',
            'auxilio' => 'conectada por Auxilio',
            'auxilio_invitado' => 'conectada por Auxilio de invitado',
            'reschedule_requested' => 'solicitada para reagenda por el paciente',
            'requested_by_professional' => 'solicitada por tu especialista',
            default => $this->status,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = method_exists($notifiable, 'isProfesional') && $notifiable->isProfesional()
            ? url('/psicologo/agenda-psicologo')
            : url('/paciente/gestion-citas');

        return (new MailMessage)
            ->subject('Actualización de cita IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('La cita '.$this->appointment->folio.' fue '.$this->label().'.')
            ->action('Ver cita', $url);
    }

    public function toArray(object $notifiable): array
    {
        $url = method_exists($notifiable, 'isProfesional') && $notifiable->isProfesional()
            ? '/psicologo/agenda-psicologo'
            : '/paciente/gestion-citas';

        return [
            'title' => 'Actualización de cita',
            'message' => 'La cita '.$this->appointment->folio.' fue '.$this->label().'.',
            'url' => $url,
            'appointment_id' => $this->appointment->id,
        ];
    }
}
