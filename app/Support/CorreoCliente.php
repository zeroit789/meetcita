<?php

namespace App\Support;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
|==============================================================================
| CorreoCliente / Client email helper
|==============================================================================
| ES: Envía un correo de la cita al cliente y a sus asistentes extra en DOS
|     envíos: el titular recibe el suyo con el enlace de «gestionar mi cita»;
|     los asistentes, uno aparte SIN ese enlace (antes iban en Cc del mismo
|     correo y cualquiera de ellos podía cancelar o cambiar la cita). Ambos
|     en el idioma de la cita.
|     Si falla el envío al titular se relanza (quien llama decide: registrar
|     o, en el recordatorio, soltar la marca para reintentar). Si falla solo
|     la copia de los asistentes se registra y no se relanza: el titular ya
|     tiene su correo.
| EN: Sends a booking email to the client and their extra attendees in TWO
|     sends: the holder gets theirs with the "manage my booking" link; the
|     attendees get a separate one WITHOUT that link (they used to be in Cc
|     of the same email and any of them could cancel or change the booking).
|     Both in the booking's language.
|     If the send to the holder fails it is rethrown (the caller decides:
|     log it or, for the reminder, release the mark to retry). If only the
|     attendees' copy fails it is logged and not rethrown: the holder already
|     has their email.
|==============================================================================
*/
class CorreoCliente
{
    /**
     * ES: $fabrica(bool $paraAsistentes) crea el Mailable de cada envío.
     *     $ahora = true envía sin cola (sendNow), como el recordatorio.
     * EN: $fabrica(bool $paraAsistentes) builds the Mailable of each send.
     *     $ahora = true sends without the queue (sendNow), like the reminder.
     *
     * @param  callable(bool): Mailable  $fabrica
     */
    public static function enviar(Appointment $cita, callable $fabrica, bool $ahora = false): void
    {
        $idioma = $cita->idiomaCita();

        // ES: Titular: con el enlace de gestionar. EN: Holder: with the manage link.
        $titular = Mail::to($cita->email)->locale($idioma);
        $ahora ? $titular->sendNow($fabrica(false)) : $titular->send($fabrica(false));

        // ES: Asistentes: el mismo correo, aparte y sin el enlace.
        // EN: Attendees: the same email, separately and without the link.
        $asistentes = $cita->emailsAsistentesExtra();
        if ($asistentes === []) {
            return;
        }

        try {
            $copia = Mail::to($asistentes)->locale($idioma);
            $ahora ? $copia->sendNow($fabrica(true)) : $copia->send($fabrica(true));
        } catch (\Throwable $e) {
            // ES: Solo la clase: el texto puede llevar direcciones. EN: Class only: the text may carry addresses.
            Log::error('Attendee copy of '.$cita->reference.' failed ('.$e::class.').');
        }
    }
}
