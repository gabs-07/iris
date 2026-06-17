<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use App\Models\LegalConsent;
use App\Models\PatientProfile;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('registro');
    }

    public function store(Request $request): RedirectResponse
    {
        $rol = $request->input('rol', 'paciente');

        $data = $request->validate([
            'rol' => ['required', 'in:paciente,psicologo,psiquiatra,doctor_interno'],
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:160'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'max:60'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'emergencia_nombre' => ['required', 'string', 'max:180'],
            'emergencia_relacion' => ['required', 'string', 'max:80'],
            'emergencia_telefono' => ['required', 'string', 'max:30'],
            'acepta_terminos' => ['accepted'],
            'acepta_privacidad' => ['accepted'],
            'acepta_datos_sensibles' => ['accepted'],
            'acepta_comunicaciones' => ['nullable'],
            'acepta_condiciones_profesionales' => [$rol === 'paciente' ? 'nullable' : 'accepted'],
            'declara_veracidad_profesional' => [$rol === 'paciente' ? 'nullable' : 'accepted'],
        ], [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'accepted' => 'Debes aceptar este acuerdo para continuar.',
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'name' => trim($data['nombre'].' '.$data['apellidos']),
            'email' => $data['email'],
            'password' => $data['password'],
            'rol' => $data['rol'],
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'genero' => $data['genero'] ?? null,
            'telefono' => $data['telefono'],
            'profile_completed' => false,
            'professional_status' => $data['rol'] === 'paciente' ? 'none' : 'incomplete',
        ]);

        EmergencyContact::create([
            'user_id' => $user->id,
            'nombre' => $data['emergencia_nombre'],
            'relacion' => $data['emergencia_relacion'],
            'telefono' => $data['emergencia_telefono'],
        ]);

        LegalConsent::create([
            'user_id' => $user->id,
            'acepta_terminos' => true,
            'acepta_privacidad' => true,
            'acepta_datos_sensibles' => true,
            'acepta_comunicaciones' => $request->boolean('acepta_comunicaciones'),
            'acepta_condiciones_profesionales' => $request->boolean('acepta_condiciones_profesionales'),
            'declara_veracidad_profesional' => $request->boolean('declara_veracidad_profesional'),
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $user->isPaciente()
            ? PatientProfile::create(['user_id' => $user->id])
            : ProfessionalProfile::create(['user_id' => $user->id, 'tipo_profesional' => $user->rol]);

        $verificationEmailSent = true;
        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            $verificationEmailSent = false;
            report($exception);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with($verificationEmailSent ? 'success' : 'warning', $verificationEmailSent
                ? 'Cuenta creada. Verifica tu correo electrónico para continuar.'
                : 'Cuenta creada, pero no se pudo enviar el correo de verificación. Revisa tu configuración SMTP o usa MAIL_MAILER=log en local.');
    }
}
