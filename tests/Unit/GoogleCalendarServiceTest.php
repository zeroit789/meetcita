<?php

namespace Tests\Unit;

use App\Models\BlockedDay;
use App\Services\GoogleCalendarService;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\Events;
use Google\Service\Calendar\Resource\Events as EventsResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * ES: Tests unitarios de GoogleCalendarService con la API de Google simulada
 *     (Mockery): ventana de consulta, agrupado en medios-slots y degradación
 *     con gracia sin credenciales.
 * EN: Unit tests for GoogleCalendarService with the Google API mocked
 *     (Mockery): query window, grouping into half-slots and graceful
 *     degradation without credentials.
 */
class GoogleCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Credenciales falsas para que el servicio se considere configurado.
     * EN: Fake credentials so the service counts as configured.
     */
    private function configurarGoogle(): void
    {
        config([
            'services.google.client_id' => 'id',
            'services.google.client_secret' => 'secret',
            'services.google.refresh_token' => 'refresh',
            'services.google.calendar_id' => 'primary',
        ]);
    }

    /**
     * ES: Servicio con el cliente de Calendar sustituido por uno cuyo recurso
     *     "events" es el mock que se pasa (sin red ni OAuth).
     * EN: Service whose Calendar client is replaced by one whose "events"
     *     resource is the given mock (no network, no OAuth).
     */
    private function servicioConEventos(EventsResource&MockInterface $eventos): GoogleCalendarService
    {
        $calendar = new Calendar(new Client);
        $calendar->events = $eventos;

        $servicio = Mockery::mock(GoogleCalendarService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $servicio->shouldReceive('servicioCalendar')->andReturn($calendar);

        return $servicio;
    }

    /**
     * ES: Evento de Google con hora de inicio y fin (ISO 8601).
     * EN: Google event with start and end time (ISO 8601).
     */
    private function evento(string $inicio, string $fin): Event
    {
        return new Event([
            'start' => ['dateTime' => $inicio],
            'end' => ['dateTime' => $fin],
        ]);
    }

    public function test_sin_credenciales_no_aporta_ocupacion_ni_llama_a_google(): void
    {
        $eventos = Mockery::mock(EventsResource::class);
        $eventos->shouldNotReceive('listEvents');

        $this->assertSame([], $this->servicioConEventos($eventos)->eventosOcupados('2026-09-22'));
    }

    public function test_la_ventana_de_consulta_cubre_exactamente_los_dias_ofrecidos(): void
    {
        $this->configurarGoogle();

        // ES: Hoy es lunes 21-09 y se ofrecen 14 laborables: hasta el jueves 08-10
        //     (18 días naturales). La consulta tiene que llegar al final de ese día.
        // EN: Today is Monday 21-09 and 14 working days are offered: up to
        //     Thursday 08-10 (18 calendar days). The query must reach that day's end.
        $eventos = Mockery::mock(EventsResource::class);
        $eventos->shouldReceive('listEvents')
            ->once()
            ->withArgs(function (string $calendario, array $opciones) {
                return $calendario === 'primary'
                    && $opciones['timeMin'] === '2026-09-21T00:00:00+02:00'
                    && $opciones['timeMax'] === '2026-10-08T23:59:59+02:00'
                    && $opciones['singleEvents'] === true;
            })
            ->andReturn(new Events(['items' => [
                $this->evento('2026-10-08T16:00:00+02:00', '2026-10-08T17:00:00+02:00'),
            ]]));

        $servicio = $this->servicioConEventos($eventos);

        // ES: El último día ofrecido también recibe su ocupación de Google.
        // EN: The last offered day also gets its Google occupancy.
        $this->assertSame(['16:00', '16:30'], $servicio->eventosOcupados('2026-10-08'));
    }

    public function test_la_ventana_crece_si_hay_dias_bloqueados(): void
    {
        $this->configurarGoogle();
        BlockedDay::create(['date' => '2026-09-24', 'reason' => 'Festivo']);

        $eventos = Mockery::mock(EventsResource::class);
        $eventos->shouldReceive('listEvents')
            ->once()
            ->withArgs(fn (string $c, array $o) => $o['timeMax'] === '2026-10-09T23:59:59+02:00')
            ->andReturn(new Events(['items' => []]));

        $this->assertSame([], $this->servicioConEventos($eventos)->eventosOcupados('2026-10-09'));
    }

    public function test_agrupa_los_eventos_en_medios_slots_e_ignora_los_de_dia_completo(): void
    {
        $this->configurarGoogle();

        $eventos = Mockery::mock(EventsResource::class);
        $eventos->shouldReceive('listEvents')->once()->andReturn(new Events(['items' => [
            // ES: 10:15-11:00 ocupa las medias horas 10:00 y 10:30.
            // EN: 10:15-11:00 takes the 10:00 and 10:30 half-hours.
            $this->evento('2026-09-22T10:15:00+02:00', '2026-09-22T11:00:00+02:00'),
            // ES: Evento en UTC: 12:00Z son las 14:00 en Madrid.
            // EN: UTC event: 12:00Z is 14:00 in Madrid.
            $this->evento('2026-09-22T12:00:00Z', '2026-09-22T12:30:00Z'),
            // ES: Día completo (solo "date"): se ignora.
            // EN: All-day event (only "date"): ignored.
            new Event(['start' => ['date' => '2026-09-22'], 'end' => ['date' => '2026-09-23']]),
        ]]));

        $servicio = $this->servicioConEventos($eventos);

        $this->assertSame(['10:00', '10:30', '14:00'], $servicio->eventosOcupados('2026-09-22'));
        // ES: Segunda consulta servida desde caché (listEvents solo una vez).
        // EN: Second lookup served from cache (listEvents only once).
        $this->assertSame([], $servicio->eventosOcupados('2026-09-23'));
    }

    public function test_si_google_falla_no_aporta_ocupacion_y_no_rompe(): void
    {
        $this->configurarGoogle();

        $eventos = Mockery::mock(EventsResource::class);
        $eventos->shouldReceive('listEvents')->andThrow(new \RuntimeException('401 Unauthorized'));
        Cache::put('gcal_access_token', ['access_token' => 'viejo'], 3300);

        $this->assertSame([], $this->servicioConEventos($eventos)->eventosOcupados('2026-09-22'));
        // ES: Un 401 invalida el token cacheado. EN: A 401 drops the cached token.
        $this->assertFalse(Cache::has('gcal_access_token'));
    }
}
