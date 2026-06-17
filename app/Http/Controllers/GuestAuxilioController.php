<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentLifecycleService;
use App\Services\AuxilioSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class GuestAuxilioController extends Controller
{
    public function show(Request $request): View
    {
        $activeAuxilio = null;
        $appointmentId = $request->session()->get('guest_auxilio_appointment_id');

        if ($appointmentId) {
            $activeAuxilio = Appointment::query()
                ->with(['patient', 'professional.professionalProfile'])
                ->whereKey($appointmentId)
                ->where('requested_by', 'auxilio_invitado')
                ->where('status', 'accepted')
                ->whereNotNull('starts_at')
                ->where('starts_at', '>=', now(config('app.timezone'))->subMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES))
                ->first();
        }

        return view('guest.auxilio', compact('activeAuxilio'));
    }

    public function request(Request $request, AuxilioSessionService $auxilio): RedirectResponse
    {
        $data = $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
            'guest_contact' => ['nullable', 'string', 'max:120'],
            'acepta_aviso_auxilio' => ['accepted'],
        ], [
            'acepta_aviso_auxilio.accepted' => 'Debes aceptar que Auxilio no sustituye servicios médicos ni emergencias gubernamentales.',
        ]);

        try {
            $appointment = $auxilio->connectGuest($data['guest_name'] ?? null, $data['guest_contact'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('warning', $exception->getMessage());
        }

        $request->session()->put('guest_auxilio_appointment_id', $appointment->id);

        return redirect()->route('guest.auxilio')
            ->with('success', 'Conectamos tu solicitud con un profesional en Modo Escucha. Puedes entrar a Zoom desde esta pantalla.');
    }

    public function finish(Request $request): RedirectResponse
    {
        $request->session()->forget('guest_auxilio_appointment_id');

        return redirect()->route('register')
            ->with('success', 'Gracias por usar Auxilio. Regístrate para guardar tu seguimiento, diario emocional y próximas citas en IRIS.');
    }
}
