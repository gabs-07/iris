<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentCaptured extends Notification
{
    use Queueable;
    public function __construct(public Payment $payment) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago confirmado en IRIS')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Confirmamos tu pago de '.$this->payment->currency.' $'.number_format((float) $this->payment->amount, 2).' por '.$this->payment->concept.'.');
    }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Pago confirmado', 'message' => $this->payment->concept, 'url' => '/dashboard', 'payment_id' => $this->payment->id];
    }
}
