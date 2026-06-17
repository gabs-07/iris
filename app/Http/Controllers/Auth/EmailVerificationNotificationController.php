<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('success', 'Correo de verificación reenviado.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo. En local usa MAIL_MAILER=log o configura un SMTP real en .env.',
            ]);
        }
    }
}
