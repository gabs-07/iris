<?php

namespace App\Notifications;

use App\Models\PatientTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReviewed extends Notification
{
    use Queueable;

    public function __construct(public PatientTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->task->review_status === 'aprobada';

        return (new MailMessage)
            ->subject($approved ? 'Tu tarea fue aprobada en IRIS' : 'Tu tarea requiere cambios en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line($approved ? 'Tu especialista aprobó la tarea: '.$this->task->title : 'Tu especialista solicitó cambios en la tarea: '.$this->task->title)
            ->line($this->task->review_feedback ?: 'Revisa los detalles dentro de IRIS.')
            ->action('Ver tarea', url('/paciente/mis-tareas'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->task->review_status === 'aprobada' ? 'Tarea aprobada' : 'Tarea con cambios solicitados',
            'message' => $this->task->title,
            'url' => '/paciente/mis-tareas',
            'task_id' => $this->task->id,
        ];
    }
}
