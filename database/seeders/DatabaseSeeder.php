<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ES: Seeder del esqueleto de Laravel (php artisan db:seed). Solo crea un
 *     usuario de prueba; las citas no necesitan datos iniciales.
 * EN: Laravel skeleton seeder (php artisan db:seed). It only creates a test
 *     user; bookings need no initial data.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * ES: Rellena la base de datos con datos de prueba.
     * EN: Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
