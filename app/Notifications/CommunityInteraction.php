<?php

namespace App\Notifications;

use App\Models\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunityInteraction extends Notification
{
    use Queueable;
    public function __construct(public CommunityPost $post, public string $message) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Nueva actividad en Comunidad IRIS')->line($this->message);
    }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Actividad en comunidad', 'message' => $this->message, 'url' => '/comunidad/comunidad#post-'.$this->post->id, 'post_id' => $this->post->id];
    }
}
