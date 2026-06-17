<?php

use App\Models\EmergencyContact;
use App\Models\PatientProfile;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Notifications\ProfessionalProfileSubmitted;
use Illuminate\Support\Facades\Notification;

it('completes a patient profile', function () {
    $user = User::factory()->create(['rol' => 'paciente', 'profile_completed' => false, 'professional_status' => 'none']);
    EmergencyContact::create(['user_id' => $user->id, 'nombre' => 'Contacto', 'relacion' => 'familiar', 'telefono' => '5500000000']);
    PatientProfile::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post('/completar-perfil', [
        'nombre' => 'Paciente',
        'apellidos' => 'Completo',
        'telefono' => '5511111111',
        'emergencia_nombre' => 'Contacto Actualizado',
        'emergencia_relacion' => 'familiar',
        'emergencia_telefono' => '5522222222',
        'motivo_consulta' => 'Deseo iniciar acompañamiento.',
    ]);

    $response->assertRedirect('/paciente/dashboard-paciente');
    expect($user->fresh()->profile_completed)->toBeTrue();
});

it('submits a professional profile for admin review', function () {
    Notification::fake();
    User::factory()->create(['rol' => 'admin', 'profile_completed' => true]);
    $professional = User::factory()->create(['rol' => 'psicologo', 'profile_completed' => false, 'professional_status' => 'incomplete']);
    EmergencyContact::create(['user_id' => $professional->id, 'nombre' => 'Contacto', 'relacion' => 'familiar', 'telefono' => '5500000000']);
    ProfessionalProfile::create(['user_id' => $professional->id, 'tipo_profesional' => 'psicologo']);

    $response = $this->actingAs($professional)->post('/completar-perfil', [
        'nombre' => 'Profesional',
        'apellidos' => 'IRIS',
        'telefono' => '5533333333',
        'emergencia_nombre' => 'Contacto Profesional',
        'emergencia_relacion' => 'familiar',
        'emergencia_telefono' => '5544444444',
        'titulo_profesional' => 'Licenciatura en Psicología',
        'especialidad_principal' => 'Terapia cognitivo conductual',
        'cedula_profesional' => 'ABC123456',
        'modalidad' => 'videollamada',
        'costo_min' => 800,
        'duracion_sesion' => 50,
    ]);

    $response->assertRedirect('/completar-perfil');
    expect($professional->fresh()->professional_status)->toBe('pending');
    Notification::assertSentTo(User::where('rol', 'admin')->first(), ProfessionalProfileSubmitted::class);
});
