<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $identifier = Str::lower(Str::random(10));

        return [
            'nombre' => 'Usuario',
            'apellidos' => 'IRIS',
            'name' => 'Usuario IRIS',
            'email' => 'usuario-'.$identifier.'@iris.local',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => 'paciente',
            'profile_completed' => false,
            'professional_status' => 'none',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
