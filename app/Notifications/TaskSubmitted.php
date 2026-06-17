<?php

namespace App\Notifications;

use App\Models\PatientTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskSubmitted extends Notification
{
    use Queueable;

    public function __construct(public PatientTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tarea entregada para revisión en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line(($this->task->patient?->nombre_completo ?? 'Un paciente').' entregó la tarea: '.$this->task->title)
            ->action('Revisar tarea', url('/psicologo/pacientes-psicologo'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tarea entregada',
            'message' => ($this->task->patient?->nombre_completo ?? 'Paciente').' entregó: '.$this->task->title,
            'url' => '/psicologo/pacientes-psicologo',
            'task_id' => $this->task->id,
        ];
    }
}
