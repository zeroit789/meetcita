<?php

namespace Tests\Feature;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Livewire\GestionarCita;
use App\Mail\AppointmentChangedToOwner;
use App\Mail\AppointmentConfirmationToClient;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\GestionCitas;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * ES: Autogestión del cliente con su enlace firmado (sin cuenta): la página
 *     GET solo muestra; cancelar y reprogramar van por POST (acciones de
 *     Livewire) y respetan el plazo mínimo. Reprogramar nunca deja una doble
 *     reserva: bloqueo de los dos días, re-comprobación dentro de la
 *     transacción excluyendo la propia cita e índice único como red.
 * EN: Client self-service with their signed link (no account): the GET page
 *     only shows; cancel and reschedule go through POST (Livewire actions)
 *     and honour the minimum notice. Rescheduling never leaves a double
 *     booking: lock on both days, re-check inside the transaction leaving the
 *     booking itself out, and the unique index as a safety net.
 */
class AutogestionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Correo falso y eventos interceptados; plazo de 24 h.
     * EN: Fake mail and intercepted events; 24 h notice.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Event::fake([AppointmentCancelled::class, AppointmentRescheduled::class]);
        config(['appointments.self_service.enabled' => true, 'appointments.self_service.hours_before' => 24]);
    }

    /**
     * ES: Cita confirmada del miércoles 23-09 a las 10:00 (fuera del plazo de 24 h).
     * EN: Confirmed booking on Wednesday 23-09 at 10:00 (beyond the 24 h notice).
     */
    private function cita(array $datos = []): Appointment
    {
        return Appointment::factory()->confirmada()->create(array_merge([
            'date' => '2026-09-23', 'time' => '10:00', 'duration' => 30, 'email' => 'cliente@example.com',
        ], $datos));
    }

    /**
     * ES: Ruta relativa firmada de la página de gestionar (como la del correo).
     * EN: Signed relative path of the manage page (like the one in the email).
     */
    private function enlace(Appointment $cita): string
    {
        return URL::signedRoute('cita.gestionar', ['reference' => $cita->reference], absolute: false);
    }

    // ── Página GET ───────────────────────────────────────────────────────

    public function test_el_correo_de_solicitud_lleva_el_enlace_firmado_de_gestionar(): void
    {
        $cita = $this->cita();

        $html = (new AppointmentConfirmationToClient($cita))->locale('es')->render();

        $this->assertStringContainsString('/cita/'.$cita->reference.'/gestionar?signature=', $html);
        $this->assertStringContainsString($cita->urlGestionar(), str_replace('&amp;', '&', $html));
    }

    public function test_el_get_con_firma_muestra_la_cita_y_no_cambia_nada(): void
    {
        $cita = $this->cita();
        $antes = $cita->fresh()->toArray();

        $respuesta = $this->get($this->enlace($cita))
            ->assertOk()
            ->assertSee($cita->reference)
            ->assertSeeLivewire(GestionarCita::class)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));

        $this->assertSame($antes, $cita->fresh()->toArray());
        Mail::assertNothingQueued();
    }

    public function test_firma_manipulada_o_ausente_da_403(): void
    {
        $cita = $this->cita();

        $this->get($this->enlace($cita).'x')->assertForbidden();
        $this->get('/cita/'.$cita->reference.'/gestionar')->assertForbidden();

        // ES: La firma de otra cita no vale para esta. EN: Another booking's signature doesn't work here.
        $otra = $this->cita(['time' => '12:00']);
        $firma = parse_url($this->enlace($otra), PHP_URL_QUERY);
        $this->get('/cita/'.$cita->reference.'/gestionar?'.$firma)->assertForbidden();
    }

    public function test_una_referencia_inexistente_con_firma_valida_da_404(): void
    {
        $this->get(URL::signedRoute('cita.gestionar', ['reference' => 'APT-XXX-XXX'], absolute: false))->assertNotFound();
    }

    public function test_con_la_autogestion_desactivada_no_hay_pagina_ni_enlace(): void
    {
        config(['appointments.self_service.enabled' => false]);
        $cita = $this->cita();

        $this->get($this->enlace($cita))->assertNotFound();
        $html = (new AppointmentConfirmationToClient($cita))->locale('es')->render();
        $this->assertStringNotContainsString('/gestionar', $html);
    }

    public function test_el_id_de_la_cita_no_se_puede_cambiar_desde_el_navegador(): void
    {
        $cita = $this->cita();
        $otra = $this->cita(['time' => '12:00']);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(GestionarCita::class, ['citaId' => $cita->id])->set('citaId', $otra->id);
    }

    // ── Cancelar ─────────────────────────────────────────────────────────

    public function test_cancelar_cancela_borra_el_evento_de_google_y_avisa_al_dueno(): void
    {
        $cita = $this->cita();
        $google = Mockery::mock(GoogleCalendarService::class)->makePartial();
        $google->shouldReceive('borrarEvento')->once();
        $this->app->instance(GoogleCalendarService::class, $google);

        Livewire::test(GestionarCita::class, ['citaId' => $cita->id])
            ->call('abrirCancelar')
            ->call('cancelar')
            ->assertSee(__('citas.manage_cancelled_done'));

        $this->assertSame('cancelada', $cita->fresh()->status);
        Mail::assertQueued(AppointmentChangedToOwner::class, fn ($m) => $m->hasTo('owner@example.com') && $m->cambio === 'cancelada');
        Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->cancelledBy === 'client' && $e->appointment->is($cita));
    }

    public function test_fuera_de_plazo_no_deja_cancelar_ni_reprogramar(): void
    {
        // ES: Hoy lunes 08:00; la cita es mañana a las 07:30: quedan menos de 24 h.
        // EN: Today Monday 08:00; the booking is tomorrow at 07:30: under 24 h left.
        $cita = $this->cita(['date' => '2026-09-22', 'time' => '07:30']);

        $this->get($this->enlace($cita))->assertOk()->assertSee(__('citas.manage_too_late', ['hours' => 24]));

        $this->assertSame(GestionCitas::FUERA_DE_PLAZO, app(GestionCitas::class)->cancelarPorCliente($cita));
        $this->assertSame(GestionCitas::FUERA_DE_PLAZO, app(GestionCitas::class)->reprogramar($cita, '2026-09-24', '10:00'));
        Livewire::test(GestionarCita::class, ['citaId' => $cita->id])->call('cancelar');

        $this->assertSame('confirmada', $cita->fresh()->status);
        Mail::assertNothingQueued();
        Event::assertNotDispatched(AppointmentCancelled::class);
    }

    public function test_una_cita_cancelada_no_se_vuelve_a_cancelar_ni_se_reprograma(): void
    {
        $cita = Appointment::factory()->cancelada()->create(['date' => '2026-09-23', 'time' => '10:00']);

        $this->assertNotSame(GestionCitas::OK, app(GestionCitas::class)->cancelarPorCliente($cita));
        $this->assertNotSame(GestionCitas::OK, app(GestionCitas::class)->reprogramar($cita, '2026-09-24', '10:00'));

        Event::assertNothingDispatched();
    }

    // ── Reprogramar ──────────────────────────────────────────────────────

    public function test_reprogramar_cambia_dia_y_hora_vuelve_a_pendiente_y_avisa(): void
    {
        $cita = $this->cita();
        $cita->google_event_id = 'evento-viejo';
        $cita->reminded_at = now();
        $cita->save();
        $google = Mockery::mock(GoogleCalendarService::class)->makePartial();
        $google->shouldReceive('eventosOcupados')->andReturn([]);
        $google->shouldReceive('borrarEventoPorId')->once()->with('evento-viejo');
        $this->app->instance(GoogleCalendarService::class, $google);

        Livewire::test(GestionarCita::class, ['citaId' => $cita->id])
            ->call('abrirReprogramar')
            ->call('elegirDia', '2026-09-24')
            ->call('elegirHora', '12:00')
            ->call('confirmarReprogramacion')
            ->assertSee(__('citas.manage_rescheduled_done'));

        $cita->refresh();
        $this->assertSame('2026-09-24', $cita->date->toDateString());
        $this->assertSame('12:00', $cita->time);
        $this->assertSame('pendiente', $cita->status);
        $this->assertNull($cita->reminded_at);
        // ES: El id viejo se suelta en la misma escritura: una confirmación posterior guarda el suyo.
        // EN: The old id is dropped in the same write: a later confirmation stores its own.
        $this->assertNull($cita->google_event_id);
        Mail::assertQueued(AppointmentChangedToOwner::class, fn ($m) => $m->cambio === 'reprogramada' && $m->fechaAnterior === '2026-09-23');
        Mail::assertQueued(AppointmentConfirmationToClient::class, fn ($m) => $m->hasTo('cliente@example.com') && $m->reprogramada);
        Event::assertDispatched(AppointmentRescheduled::class, fn ($e) => $e->previousDate === '2026-09-23'
            && $e->previousTime === '10:00' && $e->appointment->time === '12:00');
    }

    public function test_reprogramar_a_un_hueco_ocupado_falla_sin_tocar_nada(): void
    {
        $cita = $this->cita();
        $this->cita(['date' => '2026-09-24', 'time' => '12:00', 'email' => 'otra@example.com']);

        $this->assertSame(GestionCitas::OCUPADO, app(GestionCitas::class)->reprogramar($cita, '2026-09-24', '12:00'));

        $this->assertSame('2026-09-23', $cita->fresh()->date->toDateString());
        $this->assertSame('confirmada', $cita->fresh()->status);
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    public function test_la_misma_franja_no_cambia_nada_y_solapada_consigo_misma_funciona(): void
    {
        $cita = $this->cita(['duration' => 60]);

        $this->assertSame(GestionCitas::SIN_CAMBIOS, app(GestionCitas::class)->reprogramar($cita, '2026-09-23', '10:00'));
        $this->assertSame('confirmada', $cita->fresh()->status);

        // ES: 10:00-11:00 → 10:30-11:30: se pisa consigo misma, pero no con otra.
        // EN: 10:00-11:00 → 10:30-11:30: overlaps only itself, not another booking.
        $this->assertSame(GestionCitas::OK, app(GestionCitas::class)->reprogramar($cita->fresh(), '2026-09-23', '10:30'));
        $this->assertSame('10:30', $cita->fresh()->time);
    }

    public function test_dos_reprogramaciones_al_mismo_hueco_solo_gana_una(): void
    {
        $a = $this->cita();
        $b = $this->cita(['time' => '11:00', 'email' => 'b@example.com']);

        $this->assertSame(GestionCitas::OK, app(GestionCitas::class)->reprogramar($a, '2026-09-25', '09:30'));
        $this->assertSame(GestionCitas::OCUPADO, app(GestionCitas::class)->reprogramar($b, '2026-09-25', '09:30'));

        $this->assertSame(1, Appointment::whereDate('date', '2026-09-25')->where('time', '09:30')->count());
        Event::assertDispatchedTimes(AppointmentRescheduled::class, 1);
    }

    public function test_si_un_dia_esta_bloqueado_por_otra_reserva_pide_reintentar(): void
    {
        config(['appointments.booking.lock_wait_seconds' => 0]);
        $cita = $this->cita();
        Cache::lock('reserva-dia:2026-09-25', 10)->get();

        $this->assertSame(GestionCitas::REINTENTAR, app(GestionCitas::class)->reprogramar($cita, '2026-09-25', '09:30'));

        // ES: El bloqueo del día viejo se soltó: otra operación puede tomarlo.
        // EN: The old day's lock was released: another operation can take it.
        $this->assertTrue(Cache::lock('reserva-dia:2026-09-23', 10)->get());
        $this->assertSame('2026-09-23', $cita->fresh()->date->toDateString());
    }

    public function test_el_indice_unico_es_la_red_si_la_comprobacion_fallara(): void
    {
        $a = $this->cita(['date' => '2026-09-25', 'time' => '09:30']);
        $b = $this->cita(['email' => 'b@example.com']);

        // ES: Comprobación rota a propósito: dice que todo está libre.
        // EN: Deliberately broken check: it says everything is free.
        $roto = Mockery::mock(AvailabilityService::class)->makePartial();
        $roto->shouldReceive('slotSigueLibre')->andReturn(true);
        $this->app->instance(AvailabilityService::class, $roto);

        $this->assertSame(GestionCitas::OCUPADO, app(GestionCitas::class)->reprogramar($b, '2026-09-25', '09:30'));

        $this->assertSame('2026-09-23', $b->fresh()->date->toDateString());
        $this->assertSame('09:30', $a->fresh()->time);
    }

    public function test_no_se_puede_elegir_un_dia_u_hora_que_no_se_ofrece(): void
    {
        $cita = $this->cita();
        $this->cita(['date' => '2026-09-24', 'time' => '12:00', 'email' => 'otra@example.com']);

        Livewire::test(GestionarCita::class, ['citaId' => $cita->id])
            ->call('abrirReprogramar')
            ->call('elegirDia', '2026-09-26')          // ES: sábado · EN: Saturday
            ->assertSet('nuevaFecha', '')
            ->call('elegirDia', '2026-09-24')
            ->call('elegirHora', '12:00')              // ES: ocupada · EN: taken
            ->assertSet('nuevaHora', '')
            ->call('confirmarReprogramacion');

        $this->assertSame('2026-09-23', $cita->fresh()->date->toDateString());
    }

    public function test_cada_ruta_limitada_tiene_su_propio_contador_por_ip(): void
    {
        $cita = $this->cita();

        // ES: Antes todas las rutas con throttle compartían un contador por IP:
        //     abrir 6 veces la página de gestionar bloqueaba el login del panel.
        // EN: All throttled routes used to share one counter per IP: opening
        //     the manage page 6 times blocked the panel login.
        for ($i = 0; $i < 6; $i++) {
            $this->get($this->enlace($cita))->assertOk();
        }

        $this->post('/panel/login', ['password' => 'mala'])->assertStatus(302);
    }
}
