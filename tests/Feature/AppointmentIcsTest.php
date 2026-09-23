<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ES: Tests de la descarga .ics de una cita (/cita/{reference}/calendario.ics).
 * EN: Tests for a booking's .ics download (/cita/{reference}/calendario.ics).
 */
class AppointmentIcsTest extends TestCase
{
    use RefreshDatabase;

    public function test_descarga_el_ics_de_la_cita(): void
    {
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 30]);

        $respuesta = $this->get("/cita/{$cita->reference}/calendario.ics")->assertOk();

        $this->assertStringStartsWith('text/calendar', $respuesta->headers->get('Content-Type'));
        $ics = $respuesta->getContent();
        $this->assertStringContainsString('DTSTART:20260922T080000Z', $ics);
        $this->assertStringContainsString('DTEND:20260922T083000Z', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
    }

    public function test_una_cita_cancelada_sale_como_cancelled(): void
    {
        $cita = Appointment::factory()->cancelada()->create();

        $this->assertStringContainsString('STATUS:CANCELLED', $this->get("/cita/{$cita->reference}/calendario.ics")->getContent());
    }

    public function test_una_referencia_inexistente_da_404(): void
    {
        $this->get('/cita/APT-NOEXI/calendario.ics')->assertNotFound();
    }
}
