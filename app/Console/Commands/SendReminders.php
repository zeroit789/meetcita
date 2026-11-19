<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Support\Horario;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
|==============================================================================
| SendReminders — recordatorio por correo la víspera / day-before reminder
|==============================================================================
| ES: Envía al cliente un correo la víspera de su cita CONFIRMADA (en su
|     idioma, con el enlace de gestionar y el .ics firmados). «Mañana» se
|     calcula en la zona del negocio (appointments.timezone), no en la del
|     servidor. Programada cada 15 minutos en routes/console.php y sin enviar
|     antes de reminders.from (09:00 por defecto) para no escribir de
|     madrugada.
|     Una sola vez por cita aunque corran dos procesos a la vez: cada cita se
|     RECLAMA con un UPDATE condicional (whereNull('reminded_at')) ANTES de
|     enviar; si afecta 0 filas, otro proceso ya la cogió. Si el envío falla
|     se suelta (reminded_at = null) para reintentar en la siguiente vuelta.
|     Las citas sin correo (alta manual por teléfono) se saltan.
| EN: Emails the client the day before their CONFIRMED booking (in their
|     language, with the signed manage and .ics links). "Tomorrow" is
|     computed in the business zone (appointments.timezone), not the
|     server's. Scheduled every 15 minutes in routes/console.php and never
|     sent before reminders.from (09:00 by default) so as not to write at
|     night.
|     Only once per booking even with two processes at once: each booking is
|     CLAIMED with a conditional UPDATE (whereNull('reminded_at')) BEFORE
|     sending; if it affects 0 rows, another process took it. If sending
|     fails it is released (reminded_at = null) to retry on the next run.
|     Bookings without an email (manual phone entry) are skipped.
|
| Uso / Usage:  php artisan citas:recordar
|==============================================================================
*/
class SendReminders extends Command
{
    /**
     * ES: Firma de la orden. EN: Command signature.
     *
     * @var string
     */
    protected $signature = 'citas:recordar';

    /**
     * ES: Descripción de la orden. EN: Command description.
     *
     * @var string
     */
    protected $description = 'Envía el recordatorio de la víspera a las citas confirmadas de mañana / Sends the day-before reminder to tomorrow\'s confirmed bookings';

    /**
     * ES: Busca las citas de mañana sin recordatorio, reclama cada una y envía.
     * EN: Finds tomorrow's bookings without a reminder, claims each one and sends.
     */
    public function handle(): int
    {
        if (! config('appointments.reminders.enabled', true)) {
            $this->info('Reminders disabled (APPOINTMENTS_REMINDERS=false).');

            return self::SUCCESS;
        }

        $zona = (string) config('appointments.timezone', 'UTC');
        $ahora = Carbon::now($zona);

        // ES: No antes de la hora mínima del día (si está bien escrita).
        // EN: Not before the minimum time of day (if well written).
        $desde = (string) config('appointments.reminders.from', '09:00');
        if (Horario::esHora($desde) && $ahora->format('H:i') < $desde) {
            $this->info("Before {$desde}: nothing sent yet.");

            return self::SUCCESS;
        }

        $manana = $ahora->copy()->startOfDay()->addDay()->toDateString();

        // ES: Candidatas: confirmadas de mañana, sin recordatorio y con correo.
        // EN: Candidates: confirmed for tomorrow, no reminder yet and with an email.
        $ids = Appointment::query()
            ->whereDate('date', $manana)
            ->where('status', 'confirmada')
            ->whereNull('reminded_at')
            ->where('email', '!=', '')
            ->orderBy('time')
            ->pluck('id');

        $enviados = 0;
        foreach ($ids as $id) {
            // ES: Reclamar ANTES de enviar: 0 filas = otro proceso ya la cogió
            //     (o cambió de estado entre medias).
            // EN: Claim BEFORE sending: 0 rows = another process took it (or
            //     its state changed meanwhile).
            $reclamada = Appointment::whereKey($id)
                ->whereNull('reminded_at')
                ->where('status', 'confirmada')
                ->update(['reminded_at' => now()]);

            if ($reclamada === 0) {
                continue;
            }

            $cita = Appointment::find($id);
            if (! $cita || ! $cita->tieneEmail()) {
                continue;
            }

            try {
                Mail::to($cita->email)
                    ->cc($cita->emailsAsistentesExtra())
                    ->locale($cita->idiomaCita())
                    ->send(new AppointmentReminder($cita));
                $enviados++;
            } catch (\Throwable $e) {
                // ES: Se suelta para reintentar en la siguiente vuelta. Solo la clase en el log.
                // EN: Released to retry on the next run. Class only in the log.
                Appointment::whereKey($id)->update(['reminded_at' => null]);
                Log::error('Reminder for '.$cita->reference.' failed ('.$e::class.').');
            }
        }

        $this->info("Reminders sent: {$enviados}.");

        return self::SUCCESS;
    }
}
