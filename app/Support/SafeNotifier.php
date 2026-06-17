<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Throwable;

class SafeNotifier
{
    public static function notify(?object $notifiable, Notification $notification): void
    {
        if (! $notifiable || ! method_exists($notifiable, 'notify')) {
            return;
        }

        try {
            $notifiable->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
