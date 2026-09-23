<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ES: Factoría del esqueleto de Laravel para crear usuarios de prueba.
 * EN: Laravel skeleton factory to create test users.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * ES: Contraseña (hasheada una sola vez) que usa la factoría.
     * EN: The current password being used by the factory (hashed once).
     */
    protected static ?string $password;

    /**
     * ES: Estado por defecto del modelo (datos falsos con Faker).
     * EN: Define the model's default state (fake data via Faker).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * ES: Estado con el email sin verificar.
     * EN: Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
