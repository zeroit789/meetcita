<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Support\CorreoCliente;
use App\Support\Horario;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
|     se suelta (reminded_at = null) para reintentar en la siguiente vuelta,
|     solo si la cita no ha cambiado entre medias (misma revisión, día y
|     hora). Entrega «como mucho una vez»: si el proceso muere entre marcar y
|     enviar, esa cita se queda sin recordatorio (limitación documentada).
|     Se envía directamente (sendNow, sin cola): así el correo sale con el
|     estado que se acaba de comprobar, no con el que tenga la cita cuando
|     pase el worker. Las citas sin correo (alta manual por teléfono) se saltan.
| EN: Emails the client the day before their CONFIRMED booking (in their
|     language, with the signed manage and .ics links). "Tomorrow" is
|     computed in the business zone (appointments.timezone), not the
|     server's. Scheduled every 15 minutes in routes/console.php and never
|     sent before reminders.from (09:00 by default) so as not to write at
|     night.
|     Only once per booking even with two processes at once: each booking is
|     CLAIMED with a conditional UPDATE (whereNull('reminded_at')) BEFORE
|     sending; if it affects 0 rows, another process took it. If sending
|     fails it is released (reminded_at = null) to retry on the next run,
|     only if the booking didn't change meanwhile (same revision, day and
|     time). "At most once" delivery: if the process dies between marking
|     and sending, that booking gets no reminder (documented limitation).
|     Sent directly (sendNow, no queue): the email goes out with the state
|     just checked, not whatever the booking has when the worker runs.
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
        //     Con su revisión: se reclama ESA versión de la cita, no otra.
        //     With its revision: THAT version of the booking is claimed, no other.
        $candidatas = Appointment::query()
            ->whereDate('date', $manana)
            ->where('status', 'confirmada')
            ->whereNull('reminded_at')
            ->where('email', '!=', '')
            ->orderBy('time')
            ->pluck('revision', 'id');

        $enviados = 0;
        foreach ($candidatas as $id => $revision) {
            // ES: Reclamar ANTES de enviar: 0 filas = otro proceso ya la cogió
            //     (o cambió de estado entre medias).
            // EN: Claim BEFORE sending: 0 rows = another process took it (or
            //     its state changed meanwhile).
            //     También tiene que seguir siendo de mañana (pudo reprogramarse).
            //     It must also still be for tomorrow (it may have been rescheduled).
            //     Y con la misma revisión: si se reprogramó y se confirmó entre
            //     medias, esta reclamación no vale (la nueva la cogerá otra vuelta).
            //     And with the same revision: if it was rescheduled and confirmed
            //     meanwhile, this claim doesn't count (another run will take it).
            $reclamada = Appointment::whereKey($id)
                ->where('revision', $revision)
                ->whereNull('reminded_at')
                ->where('status', 'confirmada')
                ->whereDate('date', $manana)
                ->update(['reminded_at' => now()]);

            if ($reclamada === 0) {
                continue;
            }

            // ES: Se relee y se vuelve a comprobar justo antes de enviar.
            // EN: Re-read and re-checked right before sending.
            //     Si ya no es la revisión reclamada, la marca es de otro: no se envía.
            //     If it is no longer the claimed revision, the mark is someone else's: not sent.
            $cita = Appointment::find($id);
            if (! $cita || ! $cita->tieneEmail() || $cita->status !== 'confirmada'
                || $cita->date->toDateString() !== $manana || $cita->revision !== (int) $revision) {
                continue;
            }

            try {
                // ES: Titular con el enlace de gestionar; asistentes aparte y sin
                //     él. Si solo falla la copia de los asistentes, no se suelta
                //     la marca (el titular ya lo tiene).
                // EN: Holder with the manage link; attendees separately without
                //     it. If only the attendees' copy fails, the mark is not
                //     released (the holder already has it).
                CorreoCliente::enviar($cita, fn (bool $asistentes) => new AppointmentReminder($cita, paraAsistentes: $asistentes), ahora: true);
                $enviados++;
            } catch (\Throwable $e) {
                // ES: Se suelta para reintentar en la siguiente vuelta, pero SOLO si
                //     la cita sigue siendo la que se reclamó (misma revisión, día y
                //     hora): si entre medias se reprogramó y otro proceso ya
                //     reclamó el recordatorio de la hora nueva, esa marca no es
                //     nuestra y soltarla daría un recordatorio repetido. Solo la
                //     clase en el log.
                // EN: Released to retry on the next run, but ONLY if the booking
                //     is still the one claimed (same revision, day and time): if
                //     it was rescheduled meanwhile and another process already
                //     claimed the new time's reminder, that mark isn't ours and
                //     releasing it would send a duplicate. Class only in the log.
                Appointment::whereKey($id)
                    ->where('revision', $cita->revision)
                    ->whereDate('date', $manana)
                    ->where('time', $cita->time)
                    ->update(['reminded_at' => null]);
                Log::error('Reminder for '.$cita->reference.' failed ('.$e::class.').');
            }
        }

        $this->info("Reminders sent: {$enviados}.");

        return self::SUCCESS;
    }
}
