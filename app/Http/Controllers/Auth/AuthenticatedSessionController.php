<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->to($this->redirectFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget('url.intended');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ])
            ->with('success', 'Sesión cerrada correctamente.');
    }

    private function redirectFor(User $user): string
    {
        if ($user->isAdmin()) {
            return '/admin';
        }

        if ($user->isPaciente()) {
            return '/paciente/dashboard-paciente';
        }

        if ($user->isProfesional()) {
            return $user->professional_status === 'approved'
                ? '/psicologo/dashboard-psicologo'
                : '/psicologo/perfil-psicologo';
        }

        return '/';
    }
}
