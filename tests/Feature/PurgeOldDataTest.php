<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ES: Retención de datos (RGPD art. 5.1.e): el comando citas:purgar borra las
 *     citas que pasaron hace más del plazo configurado y no se han tocado desde
 *     entonces, más los registros técnicos con IP o correos caducados. Va
 *     programado a diario y ACTIVADO por defecto; plazo 0 lo desactiva.
 * EN: Data retention (GDPR art. 5.1.e): the citas:purgar command deletes the
 *     bookings that took place longer ago than the configured period and were
 *     not touched since, plus expired technical records holding IPs or emails.
 *     It is scheduled daily and ENABLED by default; a period of 0 disables it.
 */
class PurgeOldDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ES: Crea una cita con fecha y último cambio dados.
     * EN: Creates a booking with the given date and last change.
     */
    private function cita(string $fecha, string $actualizada, string $hora = '10:00'): Appointment
    {
        $cita = Appointment::factory()->create(['date' => $fecha, 'time' => $hora]);
        $cita->timestamps = false;
        $cita->forceFill(['updated_at' => Carbon::parse($actualizada)])->save();

        return $cita;
    }

    public function test_borra_las_citas_antiguas_y_conserva_las_recientes_y_futuras(): void
    {
        config(['appointments.privacy.retention_months' => 12]);
        $vieja = $this->cita('2025-08-01', '2025-08-02');           // ES: 13 meses / EN: 13 months
        $reciente = $this->cita('2026-06-01', '2026-06-02');        // ES: 4 meses / EN: 4 months
        $tocadaHaceNada = $this->cita('2025-08-01', '2026-09-01', '11:00');  // ES: cambiada hace poco / EN: changed recently
        $futura = $this->cita('2026-10-01', '2024-01-01');

        $this->artisan('citas:purgar')->assertSuccessful();

        $this->assertModelMissing($vieja);
        $this->assertModelExists($reciente);
        $this->assertModelExists($tocadaHaceNada);
        $this->assertModelExists($futura);
    }

    public function test_borra_los_trabajos_fallidos_y_la_cache_caducada(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'a1', 'connection' => 'database', 'queue' => 'default',
            'payload' => '{"email":"cliente@example.com"}', 'exception' => 'x',
            'failed_at' => Carbon::now()->subDays(45),
        ]);
        DB::table('cache')->insert(['key' => 'reserva:203.0.113.7', 'value' => '1', 'expiration' => Carbon::now()->subDay()->getTimestamp()]);
        DB::table('cache')->insert(['key' => 'vigente', 'value' => '1', 'expiration' => Carbon::now()->addDay()->getTimestamp()]);

        $this->artisan('citas:purgar')->assertSuccessful();

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(['vigente'], DB::table('cache')->pluck('key')->all());
    }

    public function test_el_modo_simular_no_borra_nada(): void
    {
        $vieja = $this->cita('2025-01-01', '2025-01-02');

        $this->artisan('citas:purgar --simular')->assertSuccessful();

        $this->assertModelExists($vieja);
    }

    public function test_un_plazo_de_cero_desactiva_la_purga(): void
    {
        config(['appointments.privacy.retention_months' => 0]);
        $vieja = $this->cita('2020-01-01', '2020-01-02');

        $this->artisan('citas:purgar')->assertSuccessful();

        $this->assertModelExists($vieja);
    }

    public function test_la_purga_esta_activada_por_defecto_y_programada_a_diario(): void
    {
        $this->assertSame(12, config('appointments.privacy.retention_months'));

        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'citas:purgar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('30 3 * * *', $eventos->first()->expression);
    }
}
