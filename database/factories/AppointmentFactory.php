<?php

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ES: Factoría de citas para los tests. Por defecto crea una cita pendiente de
 *     30 minutos, online, el lunes 21-09-2026 a las 10:00. Los estados
 *     confirmada() y cancelada() cambian solo el estado.
 * EN: Appointment factory for tests. By default it creates a pending 30-minute
 *     online booking on Monday 2026-09-21 at 10:00. The confirmada() and
 *     cancelada() states only change the status.
 *
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * ES: Estado por defecto de la cita.
     * EN: Default state of the appointment.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'date' => '2026-09-21',
            'time' => '10:00',
            'duration' => 30,
            'modality' => 'online',
            'locale' => 'es',
            'attendees' => 'Ana, Luis',
            'attendee_emails' => null,
            'message' => 'Hablar del proyecto',
            'reason' => 'Hablar del proyecto',
            'status' => 'pendiente',
        ];
    }

    /**
     * ES: Cita ya confirmada.
     * EN: Already confirmed appointment.
     */
    public function confirmada(): static
    {
        return $this->state(['status' => 'confirmada']);
    }

    /**
     * ES: Cita ya cancelada (no ocupa hueco).
     * EN: Already cancelled appointment (does not hold a slot).
     */
    public function cancelada(): static
    {
        return $this->state(['status' => 'cancelada']);
    }
}
