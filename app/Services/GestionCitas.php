<?php

namespace App\Services;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Mail\AppointmentChangedToOwner;
use App\Mail\AppointmentConfirmationToClient;
use App\Models\Appointment;
use App\Support\Eventos;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
|==============================================================================
| GestionCitas — cambios de una cita que hace el propio cliente
| GestionCitas — changes the client makes to their own booking
|==============================================================================
| ES: Lógica de la autogestión (página «gestionar mi cita» del enlace firmado):
|       - cancelarPorCliente(): cancela, borra el evento de Google y avisa al
|         dueño (correo + Telegram) y al CRM (evento).
|       - reprogramar(): cambia día y hora SIN doble reserva:
|           · bloqueos 'reserva-dia:FECHA' de los dos días (viejo y nuevo),
|             tomados en orden fijo (alfabético) y sin repetir si es el mismo
|             día, para que dos operaciones nunca se esperen en cruz;
|           · una sola transacción que relee la cita y vuelve a comprobar el
|             hueco EXCLUYENDO la propia cita de la ocupación;
|           · UPDATE de la misma fila (la referencia y el enlace siguen valiendo)
|             con el índice único parcial (date, time) como red.
|         La cita vuelve a «pendiente» (el dueño la confirma de nuevo) y el
|         recordatorio se vuelve a enviar (reminded_at = null).
|     Google, correos, Telegram y eventos van DESPUÉS de la transacción y fuera
|     del bloqueo (llamadas lentas). Devuelve un código de resultado (constantes).
| EN: Self-service logic (the signed-link "manage my booking" page):
|       - cancelarPorCliente(): cancels, deletes the Google event and tells
|         the owner (email + Telegram) and the CRM (event).
|       - reprogramar(): moves day and time WITHOUT double booking:
|           · 'reserva-dia:DATE' locks on both days (old and new), taken in a
|             fixed (alphabetical) order and not repeated for the same day, so
|             two operations never wait on each other crosswise;
|           · a single transaction that re-reads the booking and re-checks the
|             slot LEAVING the booking itself out of the occupancy;
|           · UPDATE of the same row (the reference and link stay valid) with
|             the partial unique (date, time) index as a safety net.
|         The booking goes back to "pending" (the owner confirms it again) and
|         the reminder is sent again (reminded_at = null).
|     Google, emails, Telegram and events run AFTER the transaction and
|     outside the lock (slow calls). Returns a result code (constants).
|==============================================================================
*/
class GestionCitas
{
    // ── Códigos de resultado / Result codes ─────────────────────────────────
    public const OK = 'ok';                          // ES: hecho · EN: done

    public const SIN_CAMBIOS = 'sin_cambios';        // ES: misma franja · EN: same slot

    public const OCUPADO = 'ocupado';                // ES: el hueco ya no está libre · EN: slot no longer free

    public const REINTENTAR = 'reintentar';          // ES: día bloqueado por otra operación · EN: day locked by another operation

    public const FUERA_DE_PLAZO = 'fuera_de_plazo';  // ES: pasado el plazo mínimo · EN: past the minimum notice

    public const NO_VALIDO = 'no_valido';            // ES: estado o datos no válidos · EN: invalid state or data

    // ── Cancelar / Cancel ───────────────────────────────────────────────────

    /**
     * ES: El cliente cancela su cita (solo dentro del plazo). Transición
     *     ATÓMICA con UPDATE condicional: 0 filas = ya estaba cancelada, y
     *     entonces no se avisa a nadie ni se dispara el evento.
     * EN: The client cancels their booking (only within the notice period).
     *     ATOMIC transition via conditional UPDATE: 0 rows = already
     *     cancelled, and then nobody is told and no event is fired.
     */
    public function cancelarPorCliente(Appointment $cita): string
    {
        if (! $cita->puedeAutogestionarse()) {
            return $cita->status === 'cancelada' ? self::NO_VALIDO : self::FUERA_DE_PLAZO;
        }

        $filas = Appointment::whereKey($cita->id)
            ->where('status', '!=', 'cancelada')
            ->update(['status' => 'cancelada']);

        if ($filas === 0) {
            return self::NO_VALIDO;
        }

        $cita->refresh();

        // ES: Libera el hueco también en Google (no-op sin Google o sin evento).
        // EN: Frees the slot in Google too (no-op without Google or event).
        $this->borrarEventoGoogle($cita);

        // ES: Aviso al dueño: correo y Telegram (lo mínimo: referencia, nombre, día y hora).
        // EN: Notice to the owner: email and Telegram (bare minimum: reference, name, day and time).
        $this->avisarDueno(new AppointmentChangedToOwner($cita, 'cancelada'));
        try {
            app(TelegramNotifier::class)->avisarCancelacionCliente($cita);
        } catch (\Throwable $e) {
            Log::error('Client cancellation of '.$cita->reference.': Telegram failed ('.$e::class.').');
        }

        Eventos::disparar(new AppointmentCancelled($cita, AppointmentCancelled::BY_CLIENT));

        return self::OK;
    }

    // ── Reprogramar / Reschedule ────────────────────────────────────────────

    /**
     * ES: El cliente cambia su cita a otro día y hora libres (misma duración).
     * EN: The client moves their booking to another free day and time (same duration).
     */
    public function reprogramar(Appointment $cita, string $fecha, string $hora): string
    {
        if (! $cita->puedeAutogestionarse()) {
            return $cita->status === 'cancelada' ? self::NO_VALIDO : self::FUERA_DE_PLAZO;
        }

        $disponibilidad = app(AvailabilityService::class);

        // ES: Día ofrecido, hora "HH:MM" exacta y una duración de la config.
        // EN: An offered day, an exact "HH:MM" time and a configured duration.
        if (! $disponibilidad->esFechaReservable($fecha)
            || ! preg_match('/^\d{2}:\d{2}$/', $hora)
            || ! $disponibilidad->duracionValida((int) $cita->duration)) {
            return self::NO_VALIDO;
        }

        $fechaAntes = $cita->date->toDateString();
        $horaAntes = (string) $cita->time;

        // ES: La misma franja: nada que hacer (no se degrada a pendiente ni se avisa).
        // EN: The same slot: nothing to do (not downgraded to pending, nobody told).
        if ($fecha === $fechaAntes && $hora === $horaAntes) {
            return self::SIN_CAMBIOS;
        }

        // ES: Días a bloquear: sin repetir y en orden fijo.
        // EN: Days to lock: no repeats and in a fixed order.
        $dias = array_values(array_unique([$fechaAntes, $fecha]));
        sort($dias);

        $resultado = $this->conBloqueosDeDias($dias, function () use ($cita, $fecha, $hora, $fechaAntes, $horaAntes) {
            try {
                return DB::transaction(function () use ($cita, $fecha, $hora, $fechaAntes, $horaAntes) {
                    // ES: Relectura con la cita bloqueada: si cambió o se canceló
                    //     mientras esperábamos, no seguimos.
                    // EN: Re-read with the booking locked: if it changed or was
                    //     cancelled while we waited, stop.
                    $actual = Appointment::whereKey($cita->id)->lockForUpdate()->first();
                    if (! $actual
                        || $actual->status === 'cancelada'
                        || $actual->date->toDateString() !== $fechaAntes
                        || (string) $actual->time !== $horaAntes) {
                        return self::NO_VALIDO;
                    }

                    // ES: Segunda barrera en motores que la respetan: las citas del día nuevo.
                    // EN: Second barrier on engines that honour it: the new day's bookings.
                    Appointment::whereDate('date', $fecha)->where('status', '!=', 'cancelada')->lockForUpdate()->get();

                    // ES: Re-comprobación DENTRO de la transacción, sin contar la propia cita.
                    // EN: Re-check INSIDE the transaction, not counting the booking itself.
                    if (! app(AvailabilityService::class)->slotSigueLibre($fecha, $hora, (int) $actual->duration, $actual->id)) {
                        return self::OCUPADO;
                    }

                    // ES: Misma fila: cambia día y hora, vuelve a pendiente y el
                    //     recordatorio se enviará de nuevo.
                    // EN: Same row: day and time change, back to pending and the
                    //     reminder will be sent again.
                    $actual->date = $fecha;
                    $actual->time = $hora;
                    $actual->status = 'pendiente';
                    $actual->reminded_at = null;
                    $actual->save();

                    return self::OK;
                });
            } catch (QueryException $e) {
                // ES: El índice único (date, time) saltó: el hueco se ocupó a la vez.
                //     Solo la clase: el texto lleva la SQL con datos del cliente.
                // EN: The unique (date, time) index fired: the slot was taken at
                //     the same time. Class only: the text carries the SQL with client data.
                Log::warning('Reschedule rejected by slot collision (unique index) ('.$e::class.').');

                return self::OCUPADO;
            }
        });

        if ($resultado !== self::OK) {
            return $resultado;
        }

        // ── Después de la transacción y del bloqueo / After the transaction and the lock ──
        $cita->refresh();

        // ES: El evento de Google era de la hora vieja: se borra (se creará otro al confirmar).
        // EN: The Google event was for the old time: delete it (a new one comes on confirm).
        $this->borrarEventoGoogle($cita);

        // ES: Aviso al dueño (correo + Telegram con botones, vuelve a estar pendiente).
        // EN: Notice to the owner (email + Telegram with buttons, it is pending again).
        $this->avisarDueno(new AppointmentChangedToOwner($cita, 'reprogramada', $fechaAntes, $horaAntes));
        try {
            $antes = Carbon::parse($fechaAntes)->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM').' · '.$horaAntes;
            app(TelegramNotifier::class)->avisarCitaPendiente($cita, $antes);
        } catch (\Throwable $e) {
            Log::error('Reschedule of '.$cita->reference.': Telegram failed ('.$e::class.').');
        }

        // ES: Aviso al cliente (y asistentes en Cc), en su idioma, con la cita nueva.
        // EN: Notice to the client (and attendees in Cc), in their language, with the new booking.
        if ($cita->tieneEmail()) {
            try {
                Mail::to($cita->email)
                    ->cc($cita->emailsAsistentesExtra())
                    ->locale($cita->idiomaCita())
                    ->send(new AppointmentConfirmationToClient($cita, reprogramada: true));
            } catch (\Throwable $e) {
                Log::error('Reschedule of '.$cita->reference.': client email failed ('.$e::class.').');
            }
        }

        Eventos::disparar(new AppointmentRescheduled($cita, $fechaAntes, $horaAntes));

        return self::OK;
    }

    // ── Ayudas / Helpers ────────────────────────────────────────────────────

    /**
     * ES: Ejecuta $trabajo con los bloqueos 'reserva-dia:FECHA' de esos días
     *     (ya ordenados y sin repetir; los mismos que usa la reserva pública).
     *     Si alguno no llega a tiempo devuelve REINTENTAR. Los suelta siempre,
     *     en orden inverso.
     * EN: Runs $trabajo holding the 'reserva-dia:DATE' locks of those days
     *     (already sorted and unique; the same ones the public booking uses).
     *     If any can't be taken in time it returns REINTENTAR. Always releases
     *     them, in reverse order.
     *
     * @param  array<int, string>  $dias
     */
    public function conBloqueosDeDias(array $dias, callable $trabajo): mixed
    {
        $tomados = [];

        try {
            foreach ($dias as $dia) {
                $bloqueo = Cache::lock('reserva-dia:'.$dia, 30);
                $bloqueo->block((int) config('appointments.booking.lock_wait_seconds', 5));
                $tomados[] = $bloqueo;
            }

            return $trabajo();
        } catch (LockTimeoutException) {
            return self::REINTENTAR;
        } finally {
            foreach (array_reverse($tomados) as $bloqueo) {
                $bloqueo->release();
            }
        }
    }

    /**
     * ES: Borra el evento de Google de la cita sin romper el flujo si falla.
     * EN: Deletes the booking's Google event without breaking the flow on failure.
     */
    protected function borrarEventoGoogle(Appointment $cita): void
    {
        try {
            app(GoogleCalendarService::class)->borrarEvento($cita);
        } catch (\Throwable $e) {
            Log::error('Could not delete the Google event of '.$cita->reference.' ('.$e::class.').');
        }
    }

    /**
     * ES: Envía un aviso al correo del dueño (config) sin romper el flujo.
     * EN: Sends a notice to the owner's email (config) without breaking the flow.
     */
    protected function avisarDueno(object $correo): void
    {
        try {
            Mail::to(config('appointments.brand.owner_email'))->send($correo);
        } catch (\Throwable $e) {
            Log::error('Owner notice failed ('.$e::class.').');
        }
    }
}
