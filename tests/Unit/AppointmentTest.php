<?php

namespace Tests\Unit;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ES: Tests unitarios del modelo Appointment: referencia pública, horas en UTC,
 *     enlaces de calendario y limpieza de los correos extra.
 * EN: Unit tests for the Appointment model: public reference, UTC times,
 *     calendar links and cleanup of the extra emails.
 */
class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_crear_genera_una_referencia_unica_con_el_prefijo_de_config(): void
    {
        config(['appointments.reference_prefix' => 'MC']);

        $cita = Appointment::factory()->create();

        $this->assertMatchesRegularExpression('/^MC-[A-HJ-NP-Z2-9]{5}$/', $cita->reference);
    }

    public function test_el_estado_no_se_puede_asignar_en_masa(): void
    {
        $cita = new Appointment(['name' => 'Ana', 'status' => 'confirmada']);

        $this->assertSame('pendiente', $cita->status);
    }

    public function test_inicio_y_fin_en_utc_segun_la_zona_del_negocio(): void
    {
        $cita = Appointment::factory()->make(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 60]);

        // ES: Madrid en septiembre es UTC+2. EN: Madrid in September is UTC+2.
        $this->assertSame('2026-09-22 08:00', $cita->inicioUtc()->format('Y-m-d H:i'));
        $this->assertSame('2026-09-22 09:00', $cita->finUtc()->format('Y-m-d H:i'));
        $this->assertStringContainsString('dates=20260922T080000Z%2F20260922T090000Z', $cita->urlGoogleCalendar());
    }

    public function test_la_ubicacion_es_el_meet_si_es_online_y_hay_enlace(): void
    {
        $cita = Appointment::factory()->make(['modality' => 'online']);
        $this->assertSame('Presencial', $cita->ubicacionCalendario());

        $cita->google_meet_url = 'https://meet.google.com/abc-defg-hij';
        $this->assertSame('https://meet.google.com/abc-defg-hij', $cita->ubicacionCalendario());
    }

    public function test_los_correos_extra_se_limpian_deduplican_y_excluyen_al_cliente(): void
    {
        $cita = Appointment::factory()->make([
            'email' => 'cliente@example.com',
            'attendee_emails' => ' a@example.com, A@Example.com ,no-valido, cliente@EXAMPLE.com,, b@example.com',
        ]);

        // ES: El duplicado sin distinguir mayúsculas se queda con la última grafía.
        // EN: The case-insensitive duplicate keeps the last spelling.
        $this->assertSame(['A@Example.com', 'b@example.com'], $cita->emailsAsistentesExtra());
    }

    public function test_sin_correos_extra_devuelve_lista_vacia(): void
    {
        $this->assertSame([], Appointment::factory()->make()->emailsAsistentesExtra());
    }
}
