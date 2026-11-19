<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\GoogleCalendarService;
use App\Support\Horario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * ES: Horario partido y por día de la semana (config schedule.hours) y huecos
 *     que excluyen una cita concreta (para reprogramarla sin chocar consigo
 *     misma). Sin schedule.hours manda el atajo open/close + weekdays.
 * EN: Split and per-weekday hours (config schedule.hours) and slots that
 *     leave one booking out (to reschedule it without clashing with itself).
 *     Without schedule.hours the open/close + weekdays shortcut applies.
 */
class HorarioPorDiaTest extends TestCase
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

    /**
     * ES: Lunes partido (mañana y tarde) y viernes solo de mañana.
     * EN: Split Monday (morning and afternoon) and Friday morning only.
     */
    private function horarioPartido(): void
    {
        config(['appointments.schedule.hours' => [
            1 => [['09:00', '14:00'], ['16:00', '19:00']],
            5 => [['09:00', '14:00']],
        ]]);
    }

    public function test_el_lunes_partido_tiene_huecos_de_manana_y_tarde_sin_el_descanso(): void
    {
        $this->horarioPartido();

        $slots = $this->servicio()->slotsDelDia('2026-09-21');

        $this->assertSame('09:00', $slots[0]);
        $this->assertContains('13:30', $slots);
        $this->assertNotContains('14:00', $slots);
        $this->assertNotContains('15:30', $slots);
        $this->assertContains('16:00', $slots);
        $this->assertSame('18:30', end($slots));
    }

    public function test_el_viernes_solo_tiene_manana(): void
    {
        $this->horarioPartido();

        $slots = $this->servicio()->slotsDelDia('2026-09-25');

        $this->assertSame('09:00', $slots[0]);
        $this->assertSame('13:30', end($slots));
        $this->assertNotContains('16:00', $slots);
    }

    public function test_un_dia_sin_tramos_no_es_laborable(): void
    {
        $this->horarioPartido();
        $servicio = $this->servicio();

        // ES: Martes 22-09 no tiene tramos. EN: Tuesday 22-09 has no ranges.
        $this->assertFalse($servicio->esFechaReservable('2026-09-22'));
        $this->assertSame([], $servicio->huecosLibres('2026-09-22'));
        $this->assertTrue($servicio->esFechaReservable('2026-09-25'));

        // ES: Solo lunes y viernes entre los días ofrecidos. EN: Only Mondays and Fridays offered.
        foreach ($servicio->diasDisponibles() as $dia) {
            $iso = Carbon::parse($dia['value'])->dayOfWeekIso;
            $this->assertContains($iso, [1, 5]);
        }
    }

    public function test_una_cita_no_puede_cruzar_el_descanso(): void
    {
        $this->horarioPartido();

        $inicios = $this->servicio()->huecosParaDuracion('2026-09-21', 60);

        $this->assertContains('13:00', $inicios);
        $this->assertNotContains('13:30', $inicios);   // ES: acabaría a las 14:30 · EN: would end at 14:30
        $this->assertContains('16:00', $inicios);
        $this->assertContains('18:00', $inicios);
        $this->assertNotContains('18:30', $inicios);   // ES: acabaría a las 19:30 · EN: would end at 19:30
    }

    public function test_el_texto_del_horario_agrupa_los_dias_con_el_mismo_horario(): void
    {
        app()->setLocale('es');
        config(['appointments.schedule.hours' => [
            1 => [['09:00', '14:00'], ['16:00', '19:00']],
            2 => [['09:00', '14:00'], ['16:00', '19:00']],
            3 => [['09:00', '14:00'], ['16:00', '19:00']],
            4 => [['09:00', '14:00'], ['16:00', '19:00']],
            5 => [['09:00', '14:00']],
        ]]);

        $this->assertSame(
            'lunes a jueves · 09:00–14:00 y 16:00–19:00; viernes · 09:00–14:00',
            $this->servicio()->textoHorario()
        );

        app()->setLocale('en');
        $this->assertSame(
            'Monday to Thursday · 09:00–14:00 and 16:00–19:00; Friday · 09:00–14:00',
            $this->servicio()->textoHorario()
        );
    }

    public function test_sin_horario_por_dia_el_texto_es_el_de_siempre(): void
    {
        app()->setLocale('es');

        $this->assertSame('lunes a viernes · 09:30–18:00', $this->servicio()->textoHorario());
    }

    public function test_los_tramos_mal_escritos_se_ignoran_y_sin_dias_no_se_cuelga(): void
    {
        // ES: Todo mal escrito: se ignora y manda el atajo (la página sigue funcionando).
        // EN: Everything badly written: ignored, the shortcut rules (the page keeps working).
        config(['appointments.schedule.hours' => [
            1 => [['14:00', '09:00'], ['9h', '10:00']],
            9 => [['09:00', '10:00']],
        ]]);
        $this->assertSame('09:30', $this->servicio()->slotsDelDia('2026-09-21')[0]);

        // ES: Ningún día abierto: sin días y sin colgarse. EN: No open day: no days and no hang.
        config(['appointments.schedule.hours' => [], 'appointments.schedule.weekdays' => []]);
        $servicio = $this->servicio();

        $this->assertSame([], $servicio->diasDisponibles());
        $this->assertFalse($servicio->esFechaReservable('2026-09-21'));
        $this->assertSame('2026-09-21', $servicio->rangoReservable()['min']);
    }

    public function test_el_horario_se_puede_escribir_en_una_linea_del_env(): void
    {
        $this->assertSame([
            1 => [['09:00', '14:00'], ['16:00', '19:00']],
            2 => [['09:00', '14:00'], ['16:00', '19:00']],
            3 => [['09:00', '14:00'], ['16:00', '19:00']],
            4 => [['09:00', '14:00'], ['16:00', '19:00']],
            5 => [['09:00', '14:00']],
        ], Horario::desdeTexto('1-4 09:00-14:00 16:00-19:00; 5 09:00-14:00'));

        $this->assertSame([], Horario::desdeTexto(''));
        $this->assertSame([6 => [['10:00', '13:00']]], Horario::desdeTexto('6 10:00-13:00; basura; 8 09:00-10:00'));
    }

    public function test_excluir_una_cita_libera_su_propio_tramo(): void
    {
        $cita = Appointment::factory()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 60]);
        Appointment::factory()->create(['date' => '2026-09-22', 'time' => '11:00', 'duration' => 30]);
        $servicio = $this->servicio();

        $this->assertNotContains('10:00', $servicio->huecosParaDuracion('2026-09-22', 60));
        $this->assertContains('10:00', $servicio->huecosParaDuracion('2026-09-22', 60, $cita->id));
        $this->assertContains('09:30', $servicio->huecosParaDuracion('2026-09-22', 60, $cita->id));
        // ES: La otra cita sigue ocupando. EN: The other booking still blocks.
        $this->assertNotContains('10:30', $servicio->huecosParaDuracion('2026-09-22', 60, $cita->id));
        $this->assertTrue($servicio->slotSigueLibre('2026-09-22', '09:30', 60, $cita->id));
        $this->assertFalse($servicio->slotSigueLibre('2026-09-22', '09:30', 60));
    }

    public function test_excluir_una_cita_ignora_tambien_su_evento_de_google(): void
    {
        $cita = Appointment::factory()->confirmada()->create(['date' => '2026-09-22', 'time' => '10:00', 'duration' => 60]);
        $cita->google_event_id = 'evento-propio';
        $cita->save();

        // ES: Google tiene el evento de la propia cita (10:00-11:00) y otro a las 15:00.
        // EN: Google holds the booking's own event (10:00-11:00) and another at 15:00.
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('eventosOcupados')->andReturnUsing(
            fn (string $fecha, ?string $excluir = null) => $fecha !== '2026-09-22'
                ? []
                : ($excluir === 'evento-propio' ? ['15:00'] : ['10:00', '10:30', '15:00'])
        );
        $this->app->instance(GoogleCalendarService::class, $google);

        $servicio = $this->servicio();

        $this->assertContains('10:00', $servicio->huecosParaDuracion('2026-09-22', 60, $cita->id));
        $this->assertNotContains('15:00', $servicio->huecosParaDuracion('2026-09-22', 30, $cita->id));
        $this->assertNotContains('10:00', $servicio->huecosParaDuracion('2026-09-22', 30));
    }

    public function test_los_tramos_solapados_se_fusionan(): void
    {
        $this->assertSame([1 => [['09:00', '14:00']]], Horario::normalizar([1 => [['11:00', '14:00'], ['09:00', '12:00']]]));

        config(['appointments.schedule.hours' => [1 => [['09:00', '12:00'], ['11:00', '14:00']]]]);
        $this->assertContains('11:30', $this->servicio()->huecosParaDuracion('2026-09-21', 60));
    }
}
