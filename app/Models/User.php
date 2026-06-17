<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nombre', 'apellidos', 'name', 'email', 'email_verified_at', 'password', 'rol',
        'fecha_nacimiento', 'genero', 'telefono', 'profile_completed',
        'professional_status', 'professional_submitted_at', 'professional_approved_at',
        'professional_rejected_at', 'professional_rejection_reason', 'approved_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_nacimiento' => 'date',
            'password' => 'hashed',
            'profile_completed' => 'boolean',
            'professional_submitted_at' => 'datetime',
            'professional_approved_at' => 'datetime',
            'professional_rejected_at' => 'datetime',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(($this->nombre ?: $this->name).' '.$this->apellidos) ?: $this->email;
    }

    public function initials(): string
    {
        $first = mb_substr((string) $this->nombre, 0, 1);
        $last = mb_substr((string) $this->apellidos, 0, 1);
        return mb_strtoupper(($first ?: 'I').($last ?: 'R'));
    }

    public function isAdmin(): bool { return $this->rol === 'admin'; }
    public function isInvitado(): bool { return $this->rol === 'invitado'; }
    public function isPaciente(): bool { return $this->rol === 'paciente'; }
    public function isProfesional(): bool { return in_array($this->rol, ['psicologo', 'psiquiatra', 'doctor_interno'], true); }
    public function isProfesionalAprobado(): bool { return $this->isProfesional() && $this->professional_status === 'approved'; }

    public function emergencyContact(): HasOne { return $this->hasOne(EmergencyContact::class); }
    public function legalConsent(): HasOne { return $this->hasOne(LegalConsent::class); }
    public function patientProfile(): HasOne { return $this->hasOne(PatientProfile::class); }
    public function professionalProfile(): HasOne { return $this->hasOne(ProfessionalProfile::class); }

    public function patientAppointments(): HasMany { return $this->hasMany(Appointment::class, 'patient_id'); }
    public function professionalAppointments(): HasMany { return $this->hasMany(Appointment::class, 'professional_id'); }
    public function diaryEntries(): HasMany { return $this->hasMany(DiaryEntry::class, 'patient_id'); }
    public function tasksAssigned(): HasMany { return $this->hasMany(PatientTask::class, 'patient_id'); }
    public function tasksCreated(): HasMany { return $this->hasMany(PatientTask::class, 'professional_id'); }
    public function prescriptionsReceived(): HasMany { return $this->hasMany(Prescription::class, 'patient_id'); }
    public function prescriptionsIssued(): HasMany { return $this->hasMany(Prescription::class, 'professional_id'); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function communityPosts(): HasMany { return $this->hasMany(CommunityPost::class); }
    public function communityComments(): HasMany { return $this->hasMany(CommunityComment::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->exists();
    }

    public function clinicalPatients()
    {
        return User::query()
            ->where('rol', 'paciente')
            ->whereIn('id', $this->professionalAppointments()
                ->whereIn('status', ['accepted', 'completed', 'missed'])
                ->select('patient_id'));
    }
}
