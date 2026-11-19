<?php

namespace Tests\Feature;

use App\Livewire\BookAppointment;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ES: Integridad de la reserva: el navegador no puede elegir duraciones, fechas
 *     u horas que el servidor no haya ofrecido (F2, F3); las duraciones y el
 *     horario de la config se respetan de verdad (F4, F5); la contención del
 *     bloqueo da un aviso de reintento (F8); mensaje y personas son opcionales
 *     y un mismo correo tiene un tope de citas activas.
 * EN: Booking integrity: the browser can't pick durations, dates or times the
 *     server didn't offer (F2, F3); the configured durations and hours are
 *     really honoured (F4, F5); lock contention gives a retry notice (F8);
 *     message and attendees are optional and one email has a cap of active
 *     bookings.
 */
class BookingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Wizard en el paso 3 (martes 22-09 a las 10:00) con lo mínimo obligatorio.
     * EN: Wizard on step 3 (Tuesday 2026-09-22 at 10:00) with the bare minimum.
     */
    private function wizardMinimo(string $email = 'ana@example.com', string $hora = '10:00'): Testable
    {
        return Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('selectTime', $hora)
            ->set('name', 'Ana García')
            ->set('email', $email);
    }

    public function test_la_duracion_no_se_puede_escribir_desde_el_navegador(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->wizardMinimo()->set('duration', 240);
    }

    public function test_la_fecha_y_la_hora_no_se_pueden_escribir_desde_el_navegador(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->wizardMinimo()->set('selectedTime', '10:15');
    }

    public function test_una_duracion_fuera_de_la_config_nunca_esta_libre(): void
    {
        $servicio = app(AvailabilityService::class);

        $this->assertFalse($servicio->slotSigueLibre('2026-09-22', '09:30', 240));
        $this->assertSame([], $servicio->huecosParaDuracion('2026-09-22', 240));
    }

    public function test_solo_vale_la_fecha_escrita_exacta(): void
    {
        $servicio = app(AvailabilityService::class);

        $this->assertTrue($servicio->esFechaReservable('2026-09-22'));
        foreach ([' 2026-09-22', '2026-9-22', '2026-09-22 10:00', '2026-02-31', '22-09-2026'] as $fecha) {
            $this->assertFalse($servicio->esFechaReservable($fecha), "«{$fecha}» no debería ser reservable");
        }
    }

    public function test_noventa_minutos_no_pisan_la_cita_siguiente_ni_pasan_del_cierre(): void
    {
        config(['appointments.schedule.durations' => [30, 60, 90]]);
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '11:00', 'duration' => 30]);

        $inicios = app(AvailabilityService::class)->huecosParaDuracion('2026-09-22', 90);

        $this->assertContains('09:30', $inicios);     // 09:30-11:00 cabe justo / fits exactly
        $this->assertNotContains('10:00', $inicios);  // 10:00-11:30 pisa la de las 11 / overlaps
        $this->assertNotContains('10:30', $inicios);
        $this->assertContains('16:30', $inicios);     // 16:30-18:00 termina en el cierre / ends at close
        $this->assertNotContains('17:00', $inicios);  // 17:00-18:30 pasa del cierre / runs past close
    }

    public function test_una_cita_guardada_ocupa_su_duracion_real_aunque_cambie_la_config(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 60]);
        config(['appointments.schedule.durations' => [30]]);

        $libres = app(AvailabilityService::class)->huecosParaDuracion('2026-09-22', 30);

        $this->assertNotContains('10:00', $libres);
        $this->assertNotContains('10:30', $libres);
        $this->assertContains('11:00', $libres);
    }

    public function test_noventa_minutos_se_reservan_y_bloquean_tres_medias_horas(): void
    {
        Mail::fake();
        config(['appointments.schedule.durations' => [30, 60, 90]]);

        Livewire::test(BookAppointment::class)
            ->call('selectDate', '2026-09-22')
            ->call('setDuration', 90)
            ->call('selectTime', '10:00')
            ->set('name', 'Ana García')
            ->set('email', 'ana@example.com')
            ->call('reserve')
            ->assertHasNoErrors()
            ->assertSet('step', 4);

        $libres = app(AvailabilityService::class)->huecosParaDuracion('2026-09-22', 30);
        $this->assertNotContains('11:00', $libres);
        $this->assertContains('11:30', $libres);
    }

    public function test_el_horario_de_la_pagina_sale_de_la_config(): void
    {
        config([
            'appointments.schedule.open' => '10:00',
            'appointments.schedule.close' => '14:00',
            'appointments.schedule.weekdays' => [1, 2, 3, 4],
        ]);

        $this->withCookie('locale', 'es')->get('/citas')
            ->assertOk()
            ->assertSee('10:00–14:00')
            ->assertSee('lunes a jueves')
            ->assertDontSee('9:30–18:00');
    }

    public function test_mensaje_y_personas_que_asistiran_son_opcionales(): void
    {
        Mail::fake();

        $this->wizardMinimo()->call('reserve')->assertHasNoErrors()->assertSet('step', 4);

        $cita = Appointment::sole();
        $this->assertNull($cita->message);
        $this->assertNull($cita->attendees);
    }

    public function test_un_correo_no_puede_acumular_mas_citas_activas_que_el_tope(): void
    {
        Mail::fake();
        config(['appointments.booking.max_active_per_email' => 2]);
        Appointment::factory()->create(['email' => 'Ana@Example.com', 'date' => '2026-09-23', 'time' => '10:00']);
        Appointment::factory()->create(['email' => 'ana@example.com', 'date' => '2026-09-24', 'time' => '10:00']);

        $this->wizardMinimo('ana@example.com')->call('reserve')->assertHasErrors('email');

        $this->assertSame(2, Appointment::count());
    }

    public function test_si_el_dia_esta_bloqueado_por_otra_reserva_se_pide_reintentar(): void
    {
        Mail::fake();
        // ES: Otra reserva del mismo día tiene el bloqueo; sin espera para el test.
        // EN: Another same-day booking holds the lock; no waiting for the test.
        config(['appointments.booking.lock_wait_seconds' => 0]);
        Cache::lock('reserva-dia:2026-09-22', 10)->get();

        $errores = $this->wizardMinimo()->call('reserve')->errors();

        $this->assertSame([__('citas.err_retry_soon')], $errores->get('selectedTime'));
        $this->assertSame(0, Appointment::count());
    }
}
