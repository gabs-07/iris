<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessionalProfileReviewed extends Notification
{
    use Queueable;

    public function __construct(public string $status, public ?string $reason = null) {}

    public function via(object $notifiable): array { return ['database', 'mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->status === 'approved';
        $mail = (new MailMessage)
            ->subject($approved ? 'Tu perfil profesional fue aprobado' : 'Tu perfil profesional requiere corrección')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line($approved ? 'Tu perfil profesional fue aprobado por el administrador.' : 'Tu perfil profesional fue rechazado temporalmente. Revisa las observaciones y vuelve a enviarlo.');

        if ($this->reason) {
            $mail->line('Observaciones: '.$this->reason);
        }

        return $mail->action($approved ? 'Pagar suscripción' : 'Corregir perfil', $approved ? url('/psicologo/pago-suscripcion') : url('/completar-perfil'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->status === 'approved' ? 'Perfil profesional aprobado' : 'Perfil profesional rechazado',
            'message' => $this->status === 'approved' ? 'Ya puedes activar tu suscripción profesional.' : ($this->reason ?: 'Revisa y corrige tu perfil profesional.'),
            'url' => $this->status === 'approved' ? '/psicologo/pago-suscripcion' : '/completar-perfil',
        ];
    }
}
