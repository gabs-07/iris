<?php

use App\Models\DiaryEntry;
use App\Models\User;

it('allows a completed patient to create a diary entry', function () {
    $user = User::factory()->create(['rol' => 'paciente', 'profile_completed' => true, 'professional_status' => 'none']);

    $this->actingAs($user)->post('/paciente/diario-paciente', [
        'title' => 'Mi día',
        'content' => 'Hoy pude escribir una entrada privada.',
        'date' => now()->toDateString(),
    ])->assertRedirect();

    expect(DiaryEntry::where('patient_id', $user->id)->count())->toBe(1);
});
