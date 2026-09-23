<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Services\AvailabilityService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * ES: Tests unitarios de AvailabilityService: qué días se ofrecen, qué franjas
 *     quedan libres y qué inicios valen para cada duración. "Hoy" es el lunes
 *     21-09-2026 a las 08:00 (ver Tests\TestCase).
 * EN: Unit tests for AvailabilityService: which days are offered, which slots
 *     are free and which starts are valid for each duration. "Today" is Monday
 *     2026-09-21 at 08:00 (see Tests\TestCase).
 */
class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Instancia nueva del servicio (sin memoización previa).
     * EN: Fresh service instance (no previous memoization).
     */
    private function servicio(): AvailabilityService
    {
        return new AvailabilityService;
    }

    public function test_ofrece_14_dias_laborables_empezando_hoy_y_saltando_fines_de_semana(): void
    {
        $dias = array_column($this->servicio()->diasDisponibles(), 'value');

        $this->assertCount(14, $dias);
        $this->assertSame('2026-09-21', $dias[0]);
        $this->assertSame('2026-10-08', $dias[13]);
        // ES: Ningún sábado ni domingo. EN: No Saturday or Sunday.
        foreach ($dias as $dia) {
            $this->assertLessThanOrEqual(5, Carbon::parse($dia)->dayOfWeekIso);
        }
    }

    public function test_los_dias_bloqueados_no_se_ofrecen_y_se_anade_otro_al_final(): void
    {
        BlockedDay::create(['date' => '2026-09-23', 'reason' => 'Vacaciones']);

        $servicio = $this->servicio();
        $dias = array_column($servicio->diasDisponibles(), 'value');

        $this->assertNotContains('2026-09-23', $dias);
        $this->assertCount(14, $dias);
        $this->assertSame('2026-10-09', end($dias));
        $this->assertFalse($servicio->esFechaReservable('2026-09-23'));
    }

    public function test_rango_reservable_va_del_primer_al_ultimo_dia_ofrecido(): void
    {
        $this->assertSame(
            ['min' => '2026-09-21', 'max' => '2026-10-08'],
            $this->servicio()->rangoReservable()
        );
    }

    public function test_es_fecha_reservable_rechaza_fin_de_semana_pasado_fuera_de_rango_y_basura(): void
    {
        $servicio = $this->servicio();

        $this->assertTrue($servicio->esFechaReservable('2026-09-22'));
        $this->assertFalse($servicio->esFechaReservable('2026-09-26'));   // ES: sábado · EN: Saturday
        $this->assertFalse($servicio->esFechaReservable('2026-09-18'));   // ES: pasado · EN: past
        $this->assertFalse($servicio->esFechaReservable('2026-10-09'));   // ES: fuera del rango · EN: out of range
        $this->assertFalse($servicio->esFechaReservable('no-es-fecha'));
    }

    public function test_slots_del_dia_van_de_apertura_a_cierre_exclusivo(): void
    {
        $slots = $this->servicio()->slotsDelDia();

        $this->assertCount(17, $slots);
        $this->assertSame('09:30', $slots[0]);
        $this->assertSame('17:30', end($slots));
        $this->assertNotContains('18:00', $slots);
    }

    public function test_una_cita_de_una_hora_ocupa_dos_medios_slots_y_la_cancelada_no_ocupa(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 60]);
        Appointment::factory()->cancelada()->create(['date' => '2026-09-22', 'time' => '12:00']);

        $libres = $this->servicio()->huecosLibres('2026-09-22');

        $this->assertNotContains('10:00', $libres);
        $this->assertNotContains('10:30', $libres);
        $this->assertContains('11:00', $libres);
        $this->assertContains('12:00', $libres);
    }

    public function test_hoy_no_ofrece_horas_ya_pasadas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-21 12:10', 'Europe/Madrid'));

        $libres = $this->servicio()->huecosLibres('2026-09-21');

        $this->assertNotContains('12:00', $libres);
        $this->assertSame('12:30', $libres[0]);
    }

    public function test_los_inicios_de_una_hora_necesitan_el_siguiente_medio_slot_libre_y_no_pasar_del_cierre(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '11:00', 'duration' => 30]);

        $inicios = $this->servicio()->huecosParaDuracion('2026-09-22', 60);

        $this->assertContains('09:30', $inicios);
        $this->assertNotContains('10:30', $inicios);   // ES: chocaría con la de las 11:00 · EN: would clash with 11:00
        $this->assertNotContains('11:00', $inicios);
        $this->assertContains('17:00', $inicios);
        $this->assertNotContains('17:30', $inicios);   // ES: terminaría a las 18:30 · EN: would end at 18:30
    }

    public function test_una_duracion_no_soportada_se_trata_como_30_minutos(): void
    {
        $servicio = $this->servicio();

        $this->assertSame(
            $servicio->huecosParaDuracion('2026-09-22', 30),
            $servicio->huecosParaDuracion('2026-09-22', 45)
        );
    }

    public function test_slot_sigue_libre_detecta_la_doble_reserva(): void
    {
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:30', 'duration' => 30]);
        $servicio = $this->servicio();

        $this->assertFalse($servicio->slotSigueLibre('2026-09-22', '10:30', 30));
        $this->assertFalse($servicio->slotSigueLibre('2026-09-22', '10:00', 60));
        $this->assertTrue($servicio->slotSigueLibre('2026-09-22', '10:00', 30));
    }

    public function test_slots_cubiertos_expande_la_duracion(): void
    {
        $this->assertSame(['10:00', '10:30'], $this->servicio()->slotsCubiertos('10:00', 60));
        $this->assertSame(['10:00'], $this->servicio()->slotsCubiertos('10:00', 30));
    }

    public function test_las_horas_ocupadas_en_google_no_se_ofrecen(): void
    {
        // ES: Google aporta 15:00 y 15:30 ocupados el martes.
        // EN: Google reports 15:00 and 15:30 busy on Tuesday.
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('eventosOcupados')->andReturnUsing(
            fn (string $fecha) => $fecha === '2026-09-22' ? ['15:00', '15:30'] : []
        );
        $this->app->instance(GoogleCalendarService::class, $google);

        $libres = $this->servicio()->huecosLibres('2026-09-22');

        $this->assertNotContains('15:00', $libres);
        $this->assertNotContains('15:30', $libres);
        $this->assertContains('16:00', $libres);
    }

    public function test_duraciones_sale_de_la_configuracion(): void
    {
        config(['appointments.schedule.durations' => [30, 60, 90]]);

        $this->assertSame([30, 60, 90], $this->servicio()->duraciones());
    }
}
