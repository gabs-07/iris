<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\LegalConsent;
use App\Models\User;
use App\Notifications\ProfessionalProfileSubmitted;
use App\Support\ClinicalAudit;
use App\Services\AppointmentBusinessRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ProfileCompletionController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/login');
        }

        if ($user->isAdmin()) {
            return redirect('/admin');
        }

        if ($user->isPaciente()) {
            $user->load('patientProfile', 'emergencyContact', 'legalConsent');
            return view('perfil.completar-paciente', compact('user'));
        }

        if ($user->isProfesional()) {
            $user->load('professionalProfile', 'emergencyContact', 'legalConsent');
            return view('perfil.completar-profesional', compact('user'));
        }

        return redirect('/dashboard');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/login');
        }

        if ($user->isPaciente()) {
            return $this->storePatient($request, $user);
        }

        if ($user->isProfesional()) {
            return $this->storeProfessional($request, $user);
        }

        return redirect('/dashboard');
    }

    private function storePatient(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'max:60'],
            'telefono' => ['required', 'string', 'max:30'],
            'emergencia_nombre' => ['required', 'string', 'max:180'],
            'emergencia_relacion' => ['required', 'string', 'max:80'],
            'emergencia_telefono' => ['required', 'string', 'max:30'],
            'terapia_previa' => ['nullable', 'string', 'max:80'],
            'medicacion_actual' => ['nullable', 'string', 'max:120'],
            'motivo_consulta' => ['nullable', 'string', 'max:3000'],
            'objetivos' => ['nullable', 'string', 'max:3000'],
            'ocupacion' => ['nullable', 'string', 'max:150'],
            'domicilio' => ['nullable', 'string', 'max:3000'],
            'estado_civil' => ['nullable', 'string', 'max:60'],
            'antecedentes' => ['nullable', 'string', 'max:3000'],
            'alergias' => ['nullable', 'string', 'max:3000'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->updateCommonUserData($user, $data);

        $user->emergencyContact()->updateOrCreate(['user_id' => $user->id], [
            'nombre' => $data['emergencia_nombre'],
            'relacion' => $data['emergencia_relacion'],
            'telefono' => $data['emergencia_telefono'],
        ]);

        $user->patientProfile()->updateOrCreate(['user_id' => $user->id], Arr::only($data, [
            'terapia_previa', 'medicacion_actual', 'motivo_consulta', 'objetivos',
            'ocupacion', 'domicilio', 'estado_civil', 'antecedentes', 'alergias',
        ]));

        $user->update([
            'profile_completed' => true,
            'professional_status' => 'none',
        ]);

        ClinicalAudit::log('patient.profile.completed', $user->id, $user->patientProfile, 'Paciente completó perfil clínico.');
        return redirect('/paciente/perfil-paciente')->with('success', 'Perfil guardado correctamente.');
    }

    private function storeProfessional(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'max:60'],
            'telefono' => ['required', 'string', 'max:30'],
            'emergencia_nombre' => ['required', 'string', 'max:180'],
            'emergencia_relacion' => ['required', 'string', 'max:80'],
            'emergencia_telefono' => ['required', 'string', 'max:30'],
            'titulo_profesional' => ['required', 'string', 'max:180'],
            'especialidad_principal' => ['required', 'string', 'max:180'],
            'cedula_profesional' => ['required', 'string', 'max:60'],
            'cedula_especialidad' => ['nullable', 'string', 'max:60'],
            'institucion' => ['nullable', 'string', 'max:180'],
            'posgrado' => ['nullable', 'string', 'max:180'],
            'experiencia_anios' => ['nullable'],
            'asociaciones' => ['nullable', 'string', 'max:255'],
            'modalidad' => ['nullable', 'string', 'max:60'],
            'ubicacion' => ['nullable', 'string', 'max:180'],
            'idiomas' => ['nullable', 'string', 'max:120'],
            'biografia' => ['nullable', 'string', 'max:3000'],
            'servicios' => ['nullable', 'string', 'max:3000'],
            'presentacion' => ['nullable', 'string', 'max:3000'],
            'formacion_academica_text' => ['nullable', 'string', 'max:3000'],
            'especialidades_text' => ['nullable', 'string', 'max:1000'],
            'dias_atencion' => ['nullable', 'array'],
            'dias_atencion.*' => ['string', Rule::in(['lunes','martes','miércoles','jueves','viernes','sábado','domingo'])],
            'proximo_espacio' => ['nullable', 'string', 'max:180'],
            'costo_min' => ['nullable', 'numeric', 'min:0'],
            'costo_max' => ['nullable', 'numeric', 'min:0', 'gte:costo_min'],
            'duracion_sesion' => ['nullable', 'integer', 'min:30', 'max:180'],
            'enfoques' => ['nullable', 'array'],
            'poblaciones' => ['nullable', 'array'],
            'areas' => ['nullable', 'array'],
            'disponibilidad' => ['nullable', 'array'],
            'disponibilidad.*.inicio' => ['nullable', 'date_format:H:i'],
            'disponibilidad.*.fin' => ['nullable', 'date_format:H:i'],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if (isset($data['experiencia_anios']) && ! is_numeric($data['experiencia_anios'])) {
            preg_match('/\d+/', (string) $data['experiencia_anios'], $matches);
            $data['experiencia_anios'] = isset($matches[0]) ? (int) $matches[0] : null;
        }

        app(AppointmentBusinessRules::class)->validateProfessionalProfileSchedule($data);

        $this->updateCommonUserData($user, $data);

        $user->emergencyContact()->updateOrCreate(['user_id' => $user->id], [
            'nombre' => $data['emergencia_nombre'],
            'relacion' => $data['emergencia_relacion'],
            'telefono' => $data['emergencia_telefono'],
        ]);

        $documents = $user->professionalProfile?->documentos ?? [];
        foreach ($request->file('documentos', []) as $file) {
            $documents[] = [
                'nombre_original' => $file->getClientOriginalName(),
                'ruta' => $file->store('professional-documents/'.$user->id, 'public'),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        $professionalData = Arr::only($data, [
            'titulo_profesional', 'especialidad_principal', 'cedula_profesional', 'cedula_especialidad',
            'institucion', 'posgrado', 'experiencia_anios', 'asociaciones', 'modalidad', 'ubicacion',
            'idiomas', 'biografia', 'servicios', 'presentacion', 'costo_min', 'costo_max',
            'duracion_sesion', 'enfoques', 'poblaciones', 'areas', 'disponibilidad',
            'dias_atencion', 'proximo_espacio',
        ]);

        $professionalData['formacion_academica'] = $this->linesToArray($data['formacion_academica_text'] ?? null);
        $professionalData['especialidades'] = $this->csvToArray($data['especialidades_text'] ?? null);

        $user->professionalProfile()->updateOrCreate(['user_id' => $user->id], array_merge($professionalData, [
            'tipo_profesional' => $user->rol,
            'documentos' => $documents,
            'submitted_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'approved_by' => null,
        ]));

        $user->update([
            'profile_completed' => true,
            'professional_status' => 'pending',
            'professional_submitted_at' => now(),
            'professional_approved_at' => null,
            'professional_rejected_at' => null,
            'professional_rejection_reason' => null,
            'approved_by' => null,
        ]);

        foreach (User::where('rol', 'admin')->get() as $admin) {
            \App\Support\SafeNotifier::notify($admin, new ProfessionalProfileSubmitted($user));
        }
        ClinicalAudit::log('professional.profile.submitted', null, $user->professionalProfile, 'Profesional completó y envió perfil para revisión.', ['professional_id' => $user->id]);

        return redirect('/psicologo/perfil-psicologo')->with('success', 'Perfil profesional enviado. El administrador debe autorizarlo antes de habilitar tu panel clínico.');
    }

    private function linesToArray(?string $value): array
    {
        return collect(preg_split('/\R+/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function csvToArray(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function updateCommonUserData(User $user, array $data): void
    {
        $payload = Arr::only($data, ['nombre', 'apellidos', 'fecha_nacimiento', 'genero', 'telefono']);
        $payload['name'] = trim(($data['nombre'] ?? $user->nombre).' '.($data['apellidos'] ?? $user->apellidos));

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
    }
}
