<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessionalProfileSubmitted extends Notification
{
    use Queueable;

    public function __construct(public User $professional) {}

    public function via(object $notifiable): array { return ['database', 'mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva solicitud profesional pendiente en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line($this->professional->nombre_completo.' envió su perfil profesional para revisión.')
            ->action('Revisar perfil', url('/admin/profesionales/'.$this->professional->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva solicitud profesional',
            'message' => $this->professional->nombre_completo.' envió su perfil para autorización.',
            'url' => '/admin/profesionales/'.$this->professional->id,
            'professional_id' => $this->professional->id,
        ];
    }
}
