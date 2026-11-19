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
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 30, 'status' => 'confirmada']);

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

    public function test_una_cita_pendiente_sale_como_tentative(): void
    {
        // ES: Pendiente aún no es una cita confirmada: TENTATIVE, no CONFIRMED.
        // EN: Pending is not a confirmed booking yet: TENTATIVE, not CONFIRMED.
        $cita = Appointment::factory()->create(['status' => 'pendiente']);

        $ics = $this->get("/cita/{$cita->reference}/calendario.ics")->getContent();

        $this->assertStringContainsString('STATUS:TENTATIVE', $ics);
        $this->assertStringNotContainsString('STATUS:CONFIRMED', $ics);
    }

    public function test_los_textos_del_calendario_van_en_el_idioma_de_la_cita(): void
    {
        // ES: Cita online sin enlace de Meet (sin Google) y en inglés.
        // EN: Online booking without a Meet link (no Google), in English.
        $cita = Appointment::factory()->create([
            'locale' => 'en', 'modality' => 'online', 'google_meet_url' => null, 'message' => 'Budget review',
        ]);

        $this->assertSame('Online', $cita->ubicacionCalendario());
        $this->assertStringContainsString('Topic: Budget review', $cita->descripcionCalendario());
        $this->assertStringNotContainsString('Asunto', $cita->descripcionCalendario());
    }

    public function test_el_ics_descargado_usa_el_idioma_de_la_cita_y_no_el_de_quien_lo_pide(): void
    {
        $cita = Appointment::factory()->create(['locale' => 'en', 'modality' => 'presencial', 'message' => 'Budget review']);

        $ics = $this->withCookie('locale', 'es')->get("/cita/{$cita->reference}/calendario.ics")->getContent();

        $this->assertStringContainsString('LOCATION:In person', $ics);
        $this->assertStringContainsString('Topic: Budget review', $ics);
        $this->assertStringNotContainsString('Presencial', $ics);
    }

    public function test_sin_mensaje_no_sale_la_linea_del_asunto(): void
    {
        $cita = Appointment::factory()->create(['message' => null, 'locale' => 'es']);

        $this->assertStringNotContainsString('Asunto', $cita->descripcionCalendario());
    }

    public function test_una_referencia_inexistente_da_404(): void
    {
        $this->get('/cita/APT-NOEXI/calendario.ics')->assertNotFound();
    }
}
