<?php

namespace Tests\Feature;

use App\Events\AppointmentConfirmed;
use App\Events\AppointmentRequested;
use App\Livewire\PanelCitas;
use App\Livewire\PanelNuevaCita;
use App\Mail\AppointmentConfirmed as CorreoConfirmada;
use App\Models\Appointment;
use App\Services\GestionCitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Panel útil: vistas «Hoy», «Próximas» y «Todas»; alta manual de una cita
 *     (cliente que llama por teléfono, correo opcional) con el mismo bloqueo
 *     que la reserva pública; y botón opcional de WhatsApp.
 * EN: Useful panel: "Today", "Upcoming" and "All" views; manual entry of a
 *     booking (a client who phones, optional email) with the same lock as the
 *     public booking; and an optional WhatsApp button.
 */
class PanelUtilTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Correo falso, eventos interceptados y sesión del panel abierta.
     * EN: Fake mail, intercepted events and an open panel session.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Event::fake([AppointmentRequested::class, AppointmentConfirmed::class]);
        $this->entrarAlPanel();
    }

    // ── Vistas / Views ───────────────────────────────────────────────────

    public function test_hoy_muestra_solo_las_de_hoy_y_proximas_no_las_pasadas(): void
    {
        Appointment::factory()->create(['date' => '2026-09-21', 'time' => '12:00', 'name' => 'Cita De Hoy']);
        Appointment::factory()->create(['date' => '2026-09-23', 'time' => '10:00', 'name' => 'Cita Futura']);
        Appointment::factory()->create(['date' => '2026-09-18', 'time' => '10:00', 'name' => 'Cita Pasada']);

        $panel = Livewire::test(PanelCitas::class);

        // ES: Por defecto, próximas (hoy en adelante). EN: Default: upcoming (today onwards).
        $panel->assertSet('vista', 'proximas')
            ->assertSee('Cita De Hoy')->assertSee('Cita Futura')->assertDontSee('Cita Pasada');

        $panel->call('verVista', 'hoy')
            ->assertSee('Cita De Hoy')->assertDontSee('Cita Futura')->assertDontSee('Cita Pasada');

        $panel->call('verVista', 'todas')
            ->assertSee('Cita De Hoy')->assertSee('Cita Futura')->assertSee('Cita Pasada');

        // ES: Un valor desconocido no cambia nada. EN: An unknown value changes nothing.
        $panel->call('verVista', 'otra')->assertSet('vista', 'todas');
    }

    public function test_la_vista_no_se_puede_escribir_desde_el_navegador(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(PanelCitas::class)->set('vista', 'hoy');
    }

    // ── Alta manual / Manual entry ───────────────────────────────────────

    public function test_sin_sesion_el_alta_manual_da_403(): void
    {
        session()->flush();

        Livewire::test(PanelNuevaCita::class)->assertForbidden();
    }

    public function test_alta_por_telefono_sin_correo_queda_confirmada_y_no_envia_nada(): void
    {
        Livewire::test(PanelNuevaCita::class)
            ->set('nombre', 'Luis Pérez')
            ->set('telefono', '612 345 678')
            ->set('fecha', '2026-09-22')
            ->set('duracion', 60)
            ->set('hora', '10:00')
            ->call('guardar')
            ->assertHasNoErrors();

        $cita = Appointment::sole();
        $this->assertSame('confirmada', $cita->status);
        $this->assertSame('', $cita->email);
        $this->assertSame(60, $cita->duration);
        Mail::assertNothingQueued();
        Event::assertDispatched(AppointmentRequested::class, fn ($e) => $e->source === 'panel');
        Event::assertDispatched(AppointmentConfirmed::class, fn ($e) => $e->source === 'panel');
    }

    public function test_alta_con_correo_envia_la_confirmacion(): void
    {
        Livewire::test(PanelNuevaCita::class)
            ->set('nombre', 'Ana')
            ->set('email', 'ana@example.com')
            ->set('fecha', '2026-09-22')
            ->set('hora', '10:00')
            ->call('guardar')
            ->assertHasNoErrors();

        Mail::assertQueued(CorreoConfirmada::class, fn ($m) => $m->hasTo('ana@example.com'));
    }

    public function test_el_nombre_es_obligatorio_y_el_correo_debe_ser_valido(): void
    {
        Livewire::test(PanelNuevaCita::class)
            ->set('email', 'no-es-correo')
            ->set('fecha', '2026-09-22')
            ->set('hora', '10:00')
            ->call('guardar')
            ->assertHasErrors(['nombre', 'email']);

        $this->assertSame(0, Appointment::count());
    }

    public function test_no_se_puede_dar_de_alta_en_un_hueco_ocupado(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:30']);

        Livewire::test(PanelNuevaCita::class)
            ->set('nombre', 'Ana')
            ->set('fecha', '2026-09-22')
            ->set('duracion', 60)
            ->set('hora', '10:00')
            ->call('guardar')
            ->assertHasErrors('hora');

        $this->assertSame(1, Appointment::count());
    }

    public function test_si_el_dia_esta_bloqueado_por_otra_reserva_pide_reintentar(): void
    {
        config(['appointments.booking.lock_wait_seconds' => 0]);
        Cache::lock('reserva-dia:2026-09-22', 10)->get();

        Livewire::test(PanelNuevaCita::class)
            ->set('nombre', 'Ana')
            ->set('fecha', '2026-09-22')
            ->set('hora', '10:00')
            ->call('guardar')
            ->assertHasErrors('hora');

        $this->assertSame(0, Appointment::count());
    }

    // ── WhatsApp ─────────────────────────────────────────────────────────

    public function test_el_enlace_de_whatsapp_normaliza_el_telefono(): void
    {
        config(['appointments.whatsapp.link' => true, 'appointments.whatsapp.country_code' => '34']);

        $cita = fn (?string $tel) => Appointment::factory()->make(['phone' => $tel, 'reference' => 'APT-K7P-3QX']);

        $this->assertStringStartsWith('https://wa.me/34612345678?text=', $cita('612 345 678')->urlWhatsapp());
        $this->assertStringStartsWith('https://wa.me/447700900123?text=', $cita('+44 7700 900123')->urlWhatsapp());
        $this->assertStringStartsWith('https://wa.me/34612345678?text=', $cita('0034 612-345-678')->urlWhatsapp());
        $this->assertStringContainsString('APT-K7P-3QX', urldecode($cita('612345678')->urlWhatsapp()));
        $this->assertNull($cita(null)->urlWhatsapp());
        $this->assertNull($cita('12')->urlWhatsapp());

        config(['appointments.whatsapp.link' => false]);
        $this->assertNull($cita('612 345 678')->urlWhatsapp());
    }

    public function test_el_boton_de_whatsapp_sale_solo_si_esta_activado(): void
    {
        Appointment::factory()->create(['phone' => '612 345 678']);

        Livewire::test(PanelCitas::class)->assertDontSee('wa.me');

        config(['appointments.whatsapp.link' => true]);
        Livewire::test(PanelCitas::class)
            ->assertSeeHtml('href="https://wa.me/34612345678?text=')
            ->assertSeeHtml('rel="noopener"')
            ->assertSeeHtml('target="_blank"');
    }

    public function test_whatsapp_ignora_las_extensiones_del_telefono(): void
    {
        config(['appointments.whatsapp.link' => true]);

        $cita = Appointment::factory()->make(['phone' => '612 345 678 ext. 12']);

        $this->assertStringStartsWith('https://wa.me/34612345678?text=', $cita->urlWhatsapp());
    }

    public function test_el_panel_no_confirma_una_cita_que_cambio_despues_de_cargar_la_tabla(): void
    {
        $cita = Appointment::factory()->create(['date' => '2026-09-23', 'time' => '10:00']);
        $vista = (string) $cita->updated_at->timestamp;

        $this->travel(5)->seconds();
        app(GestionCitas::class)->reprogramar($cita, '2026-09-24', '12:00');

        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id, $vista)
            ->assertHasErrors('accion');
        $this->assertSame('pendiente', $cita->fresh()->status);

        // ES: Con la versión que se ve ahora, sí. EN: With the version shown now, it works.
        Livewire::test(PanelCitas::class)
            ->call('confirmar', $cita->id, (string) $cita->fresh()->updated_at->timestamp)
            ->assertHasNoErrors();
        $this->assertSame('confirmada', $cita->fresh()->status);
    }
}
