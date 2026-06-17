<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('recuperar');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo. En local configura MAIL_MAILER=log o un SMTP real en .env.',
            ]);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Te enviamos un enlace de recuperación si el correo existe en IRIS.')
            : back()->withErrors(['email' => 'No fue posible enviar el enlace. Verifica el correo y configuración SMTP.']);
    }
}
