<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| PurgeOldData — borrado de datos caducados / expired data purge
|==============================================================================
| ES: Retención de datos (RGPD art. 5.1.e, limitación del plazo de
|     conservación). Borra lo que ya ha superado el plazo:
|       1) Citas cuya fecha Y último cambio tienen más de
|          appointments.privacy.retention_months meses (12 por defecto). Una
|          cita futura nunca se borra, aunque se pidiera hace mucho.
|       2) Sesiones sin actividad desde hace más de 30 días (guardan IP y
|          navegador), si las sesiones van en base de datos.
|       3) Entradas de caché ya caducadas (los límites por IP «reserva:IP» se
|          quedan en la tabla hasta que alguien las vuelve a leer).
|       4) Trabajos fallidos de la cola de más de 30 días (llevan el correo
|          serializado dentro, con los datos del cliente).
|     Plazo 0 = purga desactivada. Programado a diario a las 03:30 en
|     routes/console.php. Fuera de la app quedan el buzón de correo, el chat de
|     Telegram, los eventos de Google Calendar, los logs del proxy y las copias.
| EN: Data retention (GDPR art. 5.1.e, storage limitation). Deletes whatever
|     has outlived its period:
|       1) Bookings whose date AND last change are older than
|          appointments.privacy.retention_months months (12 by default). A
|          future booking is never deleted, however long ago it was requested.
|       2) Sessions idle for more than 30 days (they store IP and browser), if
|          sessions are kept in the database.
|       3) Already-expired cache entries (the per-IP limits "reserva:IP" stay
|          in the table until someone reads them again).
|       4) Queue failed jobs older than 30 days (they carry the serialised
|          email, with the client's data).
|     Period 0 = purge disabled. Scheduled daily at 03:30 in
|     routes/console.php. Outside the app remain the mailbox, the Telegram
|     chat, the Google Calendar events, the proxy logs and the backups.
|
| Uso / Usage:  php artisan citas:purgar            (ES: borra / EN: deletes)
|               php artisan citas:purgar --simular  (ES: solo cuenta / EN: only counts)
|==============================================================================
*/
class PurgeOldData extends Command
{
    /**
     * ES: Firma de la orden. EN: Command signature.
     *
     * @var string
     */
    protected $signature = 'citas:purgar {--simular : Cuenta lo que se borraría sin borrar nada / Count only, delete nothing}';

    /**
     * ES: Descripción de la orden. EN: Command description.
     *
     * @var string
     */
    protected $description = 'Borra las citas y los registros técnicos que han superado su plazo de conservación / Deletes expired bookings and technical records';

    /**
     * ES: Días que se guardan sesiones y trabajos fallidos (datos técnicos con IP o correo).
     * EN: Days sessions and failed jobs are kept (technical data holding IPs or emails).
     */
    private const DIAS_REGISTROS_TECNICOS = 30;

    /**
     * ES: Ejecuta la purga (o la simula) y deja en el log solo las cifras.
     * EN: Runs the purge (or simulates it) and logs only the counts.
     */
    public function handle(): int
    {
        $meses = (int) config('appointments.privacy.retention_months', 12);

        // ES: Plazo 0 (o negativo): purga desactivada por el dueño.
        // EN: Period 0 (or negative): purge disabled by the owner.
        if ($meses <= 0) {
            $this->info('Purga desactivada (APPOINTMENTS_RETENTION_MONTHS=0). / Purge disabled.');

            return self::SUCCESS;
        }

        $ahora = Carbon::now();
        $limiteCitas = $ahora->copy()->subMonths($meses);
        $limiteTecnico = $ahora->copy()->subDays(self::DIAS_REGISTROS_TECNICOS);

        // ES: 1) Citas: pasaron hace más del plazo y nadie las ha tocado desde entonces.
        // EN: 1) Bookings: took place longer ago than the period and untouched since.
        $citas = Appointment::query()
            ->whereDate('date', '<', $limiteCitas->toDateString())
            ->where('updated_at', '<', $limiteCitas);

        // ES: 2-4) Tablas técnicas, solo si existen. EN: 2-4) Technical tables, only if present.
        $sesiones = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('last_activity', '<', $limiteTecnico->getTimestamp())
            : null;
        $cache = Schema::hasTable('cache')
            ? DB::table('cache')->where('expiration', '<', $ahora->getTimestamp())
            : null;
        $fallidos = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->where('failed_at', '<', $limiteTecnico)
            : null;

        // ES: Recuento (para --simular y para el log). EN: Counts (for --simular and the log).
        $resumen = [
            'citas' => $citas->count(),
            'sesiones' => $sesiones?->count() ?? 0,
            'cache' => $cache?->count() ?? 0,
            'trabajos_fallidos' => $fallidos?->count() ?? 0,
        ];

        if (! $this->option('simular')) {
            $citas->delete();
            $sesiones?->delete();
            $cache?->delete();
            $fallidos?->delete();

            // ES: Solo cifras en el log, nunca datos personales.
            // EN: Only counts in the log, never personal data.
            Log::info('citas:purgar', $resumen);
        }

        foreach ($resumen as $tipo => $cuantos) {
            $this->line(sprintf('%s: %d', $tipo, $cuantos));
        }

        return self::SUCCESS;
    }
}
