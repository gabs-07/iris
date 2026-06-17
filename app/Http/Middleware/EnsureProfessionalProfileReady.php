<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessionalProfileReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isProfesional()) {
            abort(403);
        }

        if (! $user->profile_completed || in_array($user->professional_status, ['incomplete', 'rejected'], true)) {
            return redirect('/psicologo/perfil-psicologo')
                ->with('warning', 'Completa o corrige tu perfil profesional desde Mi perfil para enviarlo a autorización.');
        }

        if ($user->professional_status === 'pending') {
            return redirect('/psicologo/perfil-psicologo')
                ->with('warning', 'Tu perfil profesional está en revisión por administración. Puedes consultar el estado desde Suscripción y pagos.');
        }

        if ($user->professional_status !== 'approved') {
            return redirect('/psicologo/perfil-psicologo')
                ->with('warning', 'Tu perfil profesional debe estar completo y autorizado por administración.');
        }

        $hasActiveSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->exists();

        if (! $hasActiveSubscription && ! $request->is('psicologo/pago-suscripcion*')) {
            return redirect('/psicologo/pago-suscripcion')
                ->with('warning', 'Activa tu suscripción profesional para usar el panel clínico.');
        }

        return $next($request);
    }
}
