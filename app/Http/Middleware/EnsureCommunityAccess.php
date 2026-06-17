<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isPaciente()) {
            if (! $user->profile_completed) {
                return redirect('/paciente/perfil-paciente')->with('warning', 'Completa tu perfil desde Mi perfil para participar en la comunidad.');
            }
            return $next($request);
        }

        if ($user->isProfesional()) {
            if (! $user->profile_completed || in_array($user->professional_status, ['incomplete', 'rejected'], true)) {
                return redirect('/psicologo/perfil-psicologo')->with('warning', 'Completa o corrige tu perfil profesional antes de participar en la comunidad.');
            }

            if ($user->professional_status === 'pending') {
                return redirect('/psicologo/perfil-psicologo')->with('warning', 'Tu perfil profesional aún está en revisión por administración.');
            }

            if ($user->professional_status !== 'approved') {
                return redirect('/psicologo/perfil-psicologo')->with('warning', 'Tu perfil profesional debe estar autorizado.');
            }

            if (! $user->hasActiveSubscription()) {
                return redirect('/psicologo/pago-suscripcion')->with('warning', 'Activa tu suscripción profesional para participar en la comunidad.');
            }

            return $next($request);
        }

        abort(403);
    }
}
