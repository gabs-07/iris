<?php

namespace App\Notifications;

use App\Models\PatientTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;
    public function __construct(public PatientTask $task) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva tarea terapéutica en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Se te asignó la tarea: '.$this->task->title)
            ->action('Ver tarea', url('/paciente/mis-tareas'));
    }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Nueva tarea', 'message' => $this->task->title, 'url' => '/paciente/mis-tareas', 'task_id' => $this->task->id];
    }
}
