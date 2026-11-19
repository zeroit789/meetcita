<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Services\AvailabilityService;
use App\Support\ModoDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
|==============================================================================
| ResetDemo — reinicio de la demo pública / public demo reset
|==============================================================================
| ES: Borra TODAS las citas y días bloqueados y deja datos de ejemplo
|     (correos @example.com). Solo funciona con MEETCITA_DEMO=true: fuera de
|     la demo se niega y no borra nada. El scheduler lo lanza cada hora en
|     modo demo (routes/console.php).
| EN: Deletes ALL bookings and blocked days and leaves sample data
|     (@example.com emails). Only works with MEETCITA_DEMO=true: outside the
|     demo it refuses and deletes nothing. The scheduler runs it every hour in
|     demo mode (routes/console.php).
|==============================================================================
*/
class ResetDemo extends Command
{
    /**
     * @var string
     */
    protected $signature = 'citas:demo';

    /**
     * @var string
     */
    protected $description = 'Reinicia la demo pública con datos de ejemplo (solo con MEETCITA_DEMO=true) / Resets the public demo';

    /**
     * ES: Vacía y vuelve a sembrar la demo. EN: Empties and re-seeds the demo.
     */
    public function handle(AvailabilityService $disponibilidad): int
    {
        if (! ModoDemo::activo()) {
            $this->error('Solo en modo demo (MEETCITA_DEMO=true). No se ha borrado nada. / Demo mode only. Nothing deleted.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($disponibilidad) {
            Appointment::query()->delete();
            BlockedDay::query()->delete();

            // ES: Días reservables de ahora; la demo usa los primeros.
            // EN: Currently bookable days; the demo uses the first ones.
            $dias = array_column($disponibilidad->diasDisponibles(), 'value');

            $ejemplos = [
                ['Lucía Martín', 'confirmada', 'online', '10:00', 30],
                ['Javier Ruiz', 'pendiente', 'presencial', '12:00', 60],
                ['Marta López', 'pendiente', 'online', '16:30', 30],
                ['Pedro Sánchez', 'cancelada', 'presencial', '11:00', 30],
            ];
            foreach ($ejemplos as $i => [$nombre, $estado, $modalidad, $hora, $duracion]) {
                Appointment::create([
                    'name' => $nombre,
                    'email' => 'cliente'.($i + 1).'@example.com',
                    'date' => $dias[min($i + 1, count($dias) - 1)],
                    'time' => $hora,
                    'duration' => $duracion,
                    'modality' => $modalidad,
                    'locale' => 'es',
                    'message' => 'Cita de ejemplo de la demo.',
                    'reason' => 'Cita de ejemplo de la demo.',
                ])->forceFill(['status' => $estado])->save();
            }

            // ES: Un día bloqueado al final del rango. EN: One blocked day at the end of the range.
            BlockedDay::create(['date' => end($dias), 'reason' => 'Demo']);
        });

        $this->info('Demo reiniciada. / Demo reset.');

        return self::SUCCESS;
    }
}
