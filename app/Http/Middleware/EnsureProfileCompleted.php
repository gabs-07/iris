<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('perfil.completar.*') || $request->is('logout')) {
            return $next($request);
        }

        if ($user->isPaciente() && ! $user->profile_completed) {
            return redirect('/paciente/perfil-paciente')->with('warning', 'Completa tu perfil desde esta sección cuando tengas la información lista.');
        }

        if ($user->isProfesional() && (! $user->profile_completed || in_array($user->professional_status, ['incomplete', 'rejected'], true))) {
            return redirect('/psicologo/perfil-psicologo')->with('warning', 'Completa o corrige tu perfil profesional desde esta sección para enviarlo a autorización.');
        }

        return $next($request);
    }
}
