<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CommunityReport;
use App\Models\CommunityPost;
use App\Models\User;
use App\Notifications\ProfessionalProfileReviewed;
use App\Support\ClinicalAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'pendingProfessionals' => User::whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])
                ->where('professional_status', 'pending')
                ->where('profile_completed', true)
                ->count(),
            'appointments' => Appointment::with(['patient','professional'])->latest()->take(8)->get(),
            'notifications' => auth()->user()->unreadNotifications()->latest()->take(8)->get(),
            'pendingReports' => CommunityReport::where('status', 'pending')->count(),
        ]);
    }

    public function profesionales(): View
    {
        $professionals = User::whereIn('rol', ['psicologo', 'psiquiatra', 'doctor_interno'])
            ->where('professional_status', 'pending')
            ->where('profile_completed', true)
            ->with('professionalProfile')
            ->latest('professional_submitted_at')
            ->paginate(15);

        return view('admin.profesionales.index', compact('professionals'));
    }

    public function showProfesional(User $user): View
    {
        abort_unless($user->isProfesional(), 404);
        $user->load('professionalProfile', 'emergencyContact', 'legalConsent');
        return view('admin.profesionales.show', compact('user'));
    }

    public function approveProfesional(User $user): RedirectResponse
    {
        abort_unless($user->isProfesional(), 404);
        abort_unless($user->profile_completed && $user->professionalProfile, 422, 'El perfil profesional aún no está completo.');
        abort_unless($user->professional_status === 'pending', 422, 'Solo puedes aprobar perfiles enviados a revisión.');
        $user->update([
            'professional_status' => 'approved',
            'professional_approved_at' => now(),
            'professional_rejected_at' => null,
            'professional_rejection_reason' => null,
            'approved_by' => auth()->id(),
        ]);
        $user->professionalProfile?->update([
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
            'approved_by' => auth()->id(),
        ]);

        \App\Support\SafeNotifier::notify($user, new ProfessionalProfileReviewed('approved'));
        ClinicalAudit::log('professional.approved', null, $user->professionalProfile, 'Admin aprobó perfil profesional.', ['professional_id' => $user->id]);

        return redirect('/admin/profesionales')->with('success', 'Perfil profesional autorizado y notificado.');
    }

    public function rejectProfesional(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isProfesional(), 404);
        abort_unless(in_array($user->professional_status, ['pending', 'approved'], true), 422, 'Solo puedes rechazar perfiles enviados o aprobados.');
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $user->update([
            'professional_status' => 'rejected',
            'professional_rejected_at' => now(),
            'professional_rejection_reason' => $data['reason'],
            'professional_approved_at' => null,
        ]);
        $user->professionalProfile?->update([
            'rejected_at' => now(),
            'rejection_reason' => $data['reason'],
            'approved_at' => null,
        ]);

        \App\Support\SafeNotifier::notify($user, new ProfessionalProfileReviewed('rejected', $data['reason']));
        ClinicalAudit::log('professional.rejected', null, $user->professionalProfile, 'Admin rechazó perfil profesional.', ['professional_id' => $user->id, 'reason' => $data['reason']]);

        return redirect('/admin/profesionales')->with('success', 'Perfil profesional rechazado y notificado.');
    }


    public function communityReports(): View
    {
        $reports = CommunityReport::with(['post.user', 'user'])->latest()->paginate(20);
        return view('admin.comunidad.reportes', compact('reports'));
    }

    public function resolveCommunityReport(Request $request, CommunityReport $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:keep,hide,delete'],
        ]);

        if ($data['action'] === 'hide') {
            $report->post?->update(['status' => 'hidden']);
        } elseif ($data['action'] === 'delete') {
            $report->post?->delete();
        }

        $report->update([
            'status' => 'resolved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Reporte resuelto.');
    }

    public function usuarios(): View
    {
        $users = User::latest()->paginate(20);
        return view('admin.usuarios.index', compact('users'));
    }

    public function storeUsuario(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', 'in:invitado,paciente,psicologo,psiquiatra,doctor_interno,admin'],
            'password' => ['required', Rules\Password::defaults()],
        ]);
        User::create([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'] ?? '',
            'name' => trim($data['nombre'].' '.($data['apellidos'] ?? '')),
            'email' => $data['email'],
            'rol' => $data['rol'],
            'password' => Hash::make($data['password']),
            'professional_status' => in_array($data['rol'], ['psicologo', 'psiquiatra', 'doctor_interno'], true) ? 'incomplete' : 'none',
            'profile_completed' => $data['rol'] === 'invitado',
            'email_verified_at' => now(),
        ]);
        return back()->with('success', 'Usuario creado.');
    }
}
