<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\DiaryEntry;
use App\Models\EmergencyContact;
use App\Models\LegalConsent;
use App\Models\PatientProfile;
use App\Models\ProfessionalChatMessage;
use App\Models\ProfessionalProfile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncateTransactionalTables();

        $password = Hash::make('password');

        $admin = $this->createUser('Administrador', 'IRIS', 'admin@iris.test', 'admin', $password);
        $guest = $this->createUser('Invitado', 'Auxilio', 'invitado@iris.test', 'invitado', $password, [
            'telefono' => null,
            'profile_completed' => true,
        ]);
        $patient = $this->createUser('Paciente', 'IRIS', 'paciente@iris.test', 'paciente', $password, [
            'telefono' => '5500000001',
            'fecha_nacimiento' => '2000-06-08',
            'genero' => 'Masculino',
        ]);
        $psychAuxilio = $this->createUser('Psicóloga', 'Auxilio', 'psicologo.auxilio@iris.test', 'psicologo', $password, [
            'telefono' => '5500000002',
            'professional_status' => 'approved',
            'professional_submitted_at' => now(),
            'professional_approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
        $psychGestor = $this->createUser('Psicólogo', 'Gestor', 'psicologo.gestor@iris.test', 'psicologo', $password, [
            'telefono' => '5500000003',
            'professional_status' => 'approved',
            'professional_submitted_at' => now(),
            'professional_approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
        $psychiatrist = $this->createUser('Psiquiatra', 'IRIS', 'psiquiatra@iris.test', 'psiquiatra', $password, [
            'telefono' => '5500000004',
            'professional_status' => 'approved',
            'professional_submitted_at' => now(),
            'professional_approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        foreach ([$admin, $guest, $patient, $psychAuxilio, $psychGestor, $psychiatrist] as $user) {
            LegalConsent::create([
                'user_id' => $user->id,
                'acepta_terminos' => true,
                'acepta_privacidad' => true,
                'acepta_datos_sensibles' => true,
                'acepta_comunicaciones' => $user->rol !== 'invitado',
                'acepta_condiciones_profesionales' => $user->isProfesional(),
                'declara_veracidad_profesional' => $user->isProfesional(),
                'accepted_at' => now(),
            ]);
        }

        EmergencyContact::create([
            'user_id' => $patient->id,
            'nombre' => 'Contacto Paciente',
            'relacion' => 'Familiar',
            'telefono' => '5512345678',
        ]);

        PatientProfile::create([
            'user_id' => $patient->id,
            'terapia_previa' => 'no',
            'medicacion_actual' => 'Sin medicación registrada',
            'motivo_consulta' => 'Perfil de paciente para pruebas funcionales de IRIS.',
            'objetivos' => 'Probar diario, citas, tareas y autorización de diario.',
            'ocupacion' => 'Estudiante',
            'estado_civil' => 'No especificado',
            'antecedentes' => 'Sin antecedentes registrados.',
            'alergias' => 'No reporta',
            'clinical_history' => [],
            'clinical_attachments' => [],
        ]);

        $this->createProfessionalProfile($psychAuxilio, $admin, 'Atención de auxilio emocional', '1234567', 800, true);
        $this->createProfessionalProfile($psychGestor, $admin, 'Gestión y seguimiento de pacientes', '2345678', 800, false);
        $this->createProfessionalProfile($psychiatrist, $admin, 'Psiquiatría clínica', '3456789', 1200, false);

        foreach ([$psychAuxilio, $psychGestor, $psychiatrist] as $professional) {
            EmergencyContact::create([
                'user_id' => $professional->id,
                'nombre' => 'Contacto Profesional',
                'relacion' => 'Familiar',
                'telefono' => '5599999999',
            ]);

            Subscription::create([
                'user_id' => $professional->id,
                'plan' => 'Profesional mensual',
                'amount' => 800,
                'cycle' => 'monthly',
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'features' => 'Agenda, pacientes, solicitudes, tareas clínicas, diario autorizado y chat profesional.',
            ]);
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'professional_id' => $psychGestor->id,
            'folio' => 'CITA-DEMO-'.Str::upper(Str::random(5)),
            'reason' => 'Seguimiento clínico inicial',
            'modality' => 'Videollamada',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 50),
            'notes' => 'Cita de prueba para que el psicólogo gestor vea al paciente en su panel.',
            'status' => 'accepted',
            'payment_status' => 'waived',
            'amount' => 0,
            'requested_by' => 'profesional',
        ]);

        DiaryEntry::create([
            'patient_id' => $patient->id,
            'title' => 'Diario del '.now()->format('d/m/Y'),
            'content' => "[08:30] 🙂 · Estado: Tranquilo\nNota de prueba autorizada para el psicólogo gestor.",
            'notes' => [[
                'time' => '08:30',
                'title' => 'Nota de prueba',
                'content' => 'Nota de prueba autorizada para el psicólogo gestor.',
                'mood' => 'Tranquilo',
                'emoji' => '🙂',
                'saved_at' => now()->setTime(8, 30)->toDateTimeString(),
            ]],
            'mood' => 'Tranquilo',
            'emoji' => '🙂',
            'entry_date' => now()->toDateString(),
            'authorized_professional_id' => $psychGestor->id,
            'authorized_at' => now(),
        ]);

        ProfessionalChatMessage::create([
            'user_id' => $psychGestor->id,
            'message' => 'Canal profesional activo para interconsultas entre psicólogos y psiquiatras.',
            'tags' => ['general', 'interconsulta'],
        ]);
        ProfessionalChatMessage::create([
            'user_id' => $psychiatrist->id,
            'message' => 'Disponible para revisar casos que requieran valoración psiquiátrica o medicación.',
            'tags' => ['medicacion', 'derivacion'],
        ]);
    }

    private function createUser(string $nombre, string $apellidos, string $email, string $rol, string $password, array $extra = []): User
    {
        return User::create(array_merge([
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'name' => trim($nombre.' '.$apellidos),
            'email' => $email,
            'password' => $password,
            'rol' => $rol,
            'profile_completed' => true,
            'professional_status' => in_array($rol, ['psicologo', 'psiquiatra', 'doctor_interno'], true) ? 'approved' : 'none',
            'email_verified_at' => now(),
        ], $extra));
    }

    private function createProfessionalProfile(User $professional, User $admin, string $specialty, string $cedula, int $cost, bool $listeningMode): void
    {
        ProfessionalProfile::create([
            'user_id' => $professional->id,
            'tipo_profesional' => $professional->rol,
            'titulo_profesional' => $professional->rol === 'psiquiatra'
                ? 'Médico especialista en psiquiatría'
                : ($professional->rol === 'doctor_interno'
                    ? 'Doctor interno'
                    : 'Licenciatura en Psicología'),
            'cedula_profesional' => $cedula,
            'cedula_especialidad' => $professional->rol === 'psiquiatra' ? '8765432' : null,
            'institucion' => 'Institución académica registrada',
            'posgrado' => $professional->rol === 'psiquiatra' ? 'Especialidad en Psiquiatría' : 'Diplomado en intervención clínica',
            'especialidad_principal' => $specialty,
            'experiencia_anios' => 6,
            'modalidad' => 'virtual',
            'ubicacion' => 'Ciudad de México',
            'idiomas' => 'Español',
            'biografia' => 'Perfil profesional base para validación funcional del sistema IRIS.',
            'servicios' => $listeningMode ? 'Auxilio emocional inmediato y contención inicial.' : 'Consulta individual, seguimiento clínico e interconsulta.',
            'presentacion' => 'Atención profesional dentro del sistema IRIS.',
            'formacion_academica' => ['Formación profesional registrada', 'Formación clínica complementaria'],
            'especialidades' => [$specialty, 'Ansiedad', 'Estrés', 'Seguimiento clínico'],
            'dias_atencion' => ['lunes', 'martes', 'miércoles', 'jueves'],
            'proximo_espacio' => 'Disponible para validación',
            'costo_min' => $cost,
            'costo_max' => $cost,
            'duracion_sesion' => 50,
            'disponibilidad' => ['lunes' => ['inicio' => '09:00', 'fin' => '12:00'], 'martes' => ['inicio' => '09:00', 'fin' => '12:00'], 'miércoles' => ['inicio' => '16:00', 'fin' => '18:00'], 'jueves' => ['inicio' => '09:00', 'fin' => '12:00']],
            'modo_escucha_activo' => $listeningMode,
            'modo_escucha_activado_at' => $listeningMode ? now() : null,
            'documentos' => [],
            'submitted_at' => now(),
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
    }

    private function truncateTransactionalTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'professional_chat_messages',
            'audit_logs',
            'community_reports',
            'community_likes',
            'community_comments',
            'community_posts',
            'notifications',
            'session_notes',
            'prescriptions',
            'patient_notes',
            'patient_tasks',
            'diary_entries',
            'payments',
            'subscriptions',
            'appointments',
            'professional_profiles',
            'patient_profiles',
            'legal_consents',
            'emergency_contacts',
            'password_reset_tokens',
            'sessions',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
