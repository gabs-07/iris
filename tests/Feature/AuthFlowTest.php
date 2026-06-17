<?php

use App\Models\EmergencyContact;
use App\Models\LegalConsent;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('registers a patient with the minimal IRIS fields and sends email verification', function () {
    Notification::fake();

    $response = $this->post('/registro', [
        'rol' => 'paciente',
        'nombre' => 'Paciente',
        'apellidos' => 'IRIS',
        'telefono' => '5512345678',
        'email' => 'paciente-iris@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'emergencia_nombre' => 'Contacto IRIS',
        'emergencia_relacion' => 'familiar',
        'emergencia_telefono' => '5599999999',
        'acepta_terminos' => '1',
        'acepta_privacidad' => '1',
        'acepta_datos_sensibles' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $user = User::where('email', 'paciente-iris@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->profile_completed)->toBeFalse()
        ->and($user->professional_status)->toBe('none');

    expect(EmergencyContact::where('user_id', $user->id)->exists())->toBeTrue();
    expect(LegalConsent::where('user_id', $user->id)->exists())->toBeTrue();
    expect(PatientProfile::where('user_id', $user->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('authenticates a verified user and redirects according to incomplete profile', function () {
    $user = User::factory()->create(['profile_completed' => false]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/completar-perfil');
});

it('sends a password reset notification', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/recuperar', ['email' => $user->email])->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('logs out using the POST route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});
