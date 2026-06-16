<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRejected;
use App\Models\Appointment;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
|==============================================================================
| TelegramWebhookController / Webhook del bot de Telegram
|==============================================================================
| EN: Telegram bot webhook. Handles managing bookings from the owner's phone:
|       - "✅ Confirm" button (callback ac:ID)  -> booking confirmed + email to client.
|       - "❌ Not possible" button (ar:ID)       -> ask for the reason via chat.
|       - Owner's next text message              -> that reason cancels + email.
|     Security: Telegram sends the secret in the X-Telegram-Bot-Api-Secret-Token
|     header (validated) and only actions from the owner's chat_id are handled
|     (config services.telegram.chat_id).
| ES: Webhook del bot de Telegram. Gestiona las citas desde el móvil del dueño:
|       - Botón "✅ Confirmar" (callback ac:ID)  -> cita confirmada + email al cliente.
|       - Botón "❌ No me es posible" (ar:ID)     -> pide el motivo por chat.
|       - Siguiente mensaje de texto del dueño   -> ese motivo cancela la cita + email.
|     Seguridad: Telegram envía el secreto en la cabecera
|     X-Telegram-Bot-Api-Secret-Token (se valida) y solo se atienden acciones del
|     chat_id del dueño (config services.telegram.chat_id).
|
| INDEX / ÍNDICE
|   1. HANDLE ......... webhook entry point (secret + dispatch) / entrada
|   2. CALLBACK ....... button taps: confirm / reject / clics en botones
|   3. TEXT ........... reason message → cancel / mensaje de motivo → cancelar
|   4. HELPERS ........ send mail + status summary / envío de mail y resumen
|==============================================================================
*/
class TelegramWebhookController extends Controller
{
    // ── 1. Handle — punto de entrada del webhook ────────────────────────────

    /**
     * EN: Webhook entry point: Telegram calls here on every event (POST). Decides
     *     what to do by update type — button click (callback_query) or text
     *     message — and applies the action on the booking (confirm/cancel).
     *     ALWAYS returns 200 'ok' to Telegram (except 403 if the secret mismatches),
     *     because a 5xx would make Telegram retry the same update.
     * ES: Punto de entrada del webhook: Telegram llama aquí en cada evento (POST).
     *     Decide qué hacer según el tipo de update —clic en botón (callback_query)
     *     o mensaje de texto— y aplica la acción sobre la cita (confirmar/cancelar).
     *     Devuelve SIEMPRE 200 'ok' (salvo 403 si el secreto no cuadra), porque un
     *     error 5xx haría que Telegram reintentara el mismo update.
     */
    public function handle(Request $request, TelegramNotifier $tg)
    {
        // EN: 1) Validate the webhook secret (anti-spoofing). Fail-closed: if no
        //        secret is configured or it doesn't match, reject (fix M1).
        // ES: 1) Validar el secreto del webhook (anti-suplantación). Fail-closed:
        //        si no hay secreto configurado o no coincide, se rechaza (fix M1).
        $secret = config('services.telegram.webhook_secret');
        if (! $secret || $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response('forbidden', 403);
        }

        $update = $request->all();
        $ownerChatId = (string) config('services.telegram.chat_id');

        try {
            // ── 2. Callback — clic en un botón inline (confirmar / rechazar) ──
            if (isset($update['callback_query'])) {
                $cb = $update['callback_query'];
                $chatId = (string) ($cb['message']['chat']['id'] ?? '');
                $messageId = $cb['message']['message_id'] ?? null;
                $data = $cb['data'] ?? '';

                // EN: Only the owner may operate. ES: Solo el dueño puede operar.
                if ($chatId !== $ownerChatId) {
                    $tg->responderCallback($cb['id'], 'No autorizado.');

                    return response('ok');
                }

                [$accion, $citaId] = array_pad(explode(':', $data, 2), 2, null);
                $cita = Appointment::find((int) $citaId);

                if (! $cita) {
                    $tg->responderCallback($cb['id'], 'Esa cita ya no existe.');

                    return response('ok');
                }

                // EN: Only act on PENDING bookings (fix C3): avoids re-confirming
                //     or cancelling already-resolved bookings and duplicate emails.
                // ES: Solo se actúa sobre citas PENDIENTES (fix C3): evita
                //     reconfirmar/cancelar citas ya resueltas y emails duplicados.
                if ($cita->status !== 'pendiente') {
                    $tg->responderCallback($cb['id'], "Esa cita ya está {$cita->status}.");
                    if ($messageId) {
                        $tg->editarMensaje((int) $messageId, $this->resumen($cita, "ℹ️ Ya estaba <b>" . mb_strtoupper($cita->status) . "</b>"));
                    }

                    return response('ok');
                }

                if ($accion === 'ac') {
                    // EN: CONFIRM (idempotent, fix C3): ATOMIC transition
                    //     'pendiente' -> 'confirmada' via conditional UPDATE. If it
                    //     affects 0 rows, another request resolved it meanwhile:
                    //     return 200 and exit without resending email or creating event.
                    // ES: CONFIRMAR (idempotente, fix C3): transición ATÓMICA de
                    //     'pendiente' -> 'confirmada' con UPDATE condicional. Si
                    //     afecta 0 filas, otra petición ya la resolvió: respondemos
                    //     200 y salimos sin reenviar email ni crear evento.
                    $n = Appointment::where('id', $cita->id)
                        ->where('status', 'pendiente')
                        ->update(['status' => 'confirmada']);

                    if ($n === 0) {
                        // EN: Already resolved (double click / race): don't duplicate anything.
                        // ES: Ya estaba resuelta (doble clic / carrera): no duplicamos nada.
                        $tg->responderCallback($cb['id'], 'Esa cita ya estaba resuelta.');

                        return response('ok');
                    }

                    // EN: $n === 1: we confirmed it. Reload with the new status and
                    //     fire the calendar event + email.
                    // ES: $n === 1: la confirmamos nosotros. Recargamos con el estado
                    //     nuevo y disparamos evento de calendario + email.
                    $cita = Appointment::find($cita->id);

                    // EN: Create the Google Calendar event (+ Meet if online). The
                    //     service degrades gracefully if Google isn't configured.
                    // ES: Crear el evento en Google Calendar (+ Meet si es online).
                    //     El servicio degrada con gracia si Google no está configurado.
                    app(\App\Services\GoogleCalendarService::class)->crearEvento($cita);

                    $this->enviarMail($cita->email, new AppointmentConfirmed($cita), $cita->emailsAsistentesExtra(), $cita->locale ?? config('appointments.default_locale'));

                    $tg->responderCallback($cb['id'], '✅ Cita confirmada');
                    if ($messageId) {
                        $tg->editarMensaje((int) $messageId, $this->resumen($cita, '✅ <b>CONFIRMADA</b> · email enviado al cliente'));
                    }
                } elseif ($accion === 'ar') {
                    // EN: REJECT -> ask for the reason via the next message.
                    // ES: RECHAZAR -> pedir el motivo por el siguiente mensaje.
                    Cache::put("tg_motivo_{$ownerChatId}", $cita->id, now()->addMinutes(15));

                    $tg->responderCallback($cb['id'], 'Escribe el motivo…');
                    $tg->enviar(
                        "✍️ Escribe el <b>motivo</b> por el que no puedes atender la cita "
                        . "<code>{$cita->reference}</code> de " . e($cita->name) . ".\n"
                        . "<i>Se le enviará por email tal cual lo escribas.</i>"
                    );
                }

                return response('ok');
            }

            // ── 3. Text — mensaje de texto (posible motivo de rechazo) ───────
            if (isset($update['message']['text'])) {
                $msg = $update['message'];
                $chatId = (string) ($msg['chat']['id'] ?? '');
                $texto = trim($msg['text']);

                if ($chatId !== $ownerChatId) {
                    return response('ok');
                }

                $citaIdEsperando = Cache::get("tg_motivo_{$ownerChatId}");

                if ($citaIdEsperando && $texto !== '' && ! str_starts_with($texto, '/')) {
                    $cita = Appointment::find((int) $citaIdEsperando);
                    Cache::forget("tg_motivo_{$ownerChatId}");

                    if ($cita) {
                        // EN: Limit the reason length (fix I3). ES: Limitamos el motivo (fix I3).
                        $motivo = \Illuminate\Support\Str::limit($texto, 1000);

                        // EN: CANCEL (idempotent, fix C3): ATOMIC transition
                        //     'pendiente' -> 'cancelada' via conditional UPDATE. If
                        //     it affects 0 rows, the booking wasn't pending anymore:
                        //     don't touch Google nor resend email.
                        // ES: CANCELAR (idempotente, fix C3): transición ATÓMICA de
                        //     'pendiente' -> 'cancelada' con UPDATE condicional. Si
                        //     afecta 0 filas, la cita ya no estaba pendiente: no
                        //     tocamos Google ni reenviamos email.
                        $n = Appointment::where('id', $cita->id)
                            ->where('status', 'pendiente')
                            ->update(['status' => 'cancelada']);

                        if ($n === 1) {
                            // EN: We cancelled it. Reload with the new status.
                            // ES: La cancelamos nosotros. Recargamos con el estado nuevo.
                            $cita = Appointment::find($cita->id);

                            // EN: Delete the Google Calendar event (frees the slot in
                            //     Google and notifies the client). No-op if no event.
                            // ES: Borrar el evento de Google Calendar (libera el hueco
                            //     y avisa al cliente). No-op si no hay evento.
                            app(\App\Services\GoogleCalendarService::class)->borrarEvento($cita);

                            $this->enviarMail($cita->email, new AppointmentRejected($cita, $motivo), $cita->emailsAsistentesExtra(), $cita->locale ?? config('appointments.default_locale'));

                            $tg->enviar(
                                "❌ Cita <code>{$cita->reference}</code> de " . e($cita->name)
                                . " cancelada. Le he enviado tu motivo por email."
                            );
                        } else {
                            // EN: The booking changed state meanwhile (fix C3).
                            // ES: La cita cambió de estado entre medias (fix C3).
                            $tg->enviar("ℹ️ La cita <code>{$cita->reference}</code> ya estaba {$cita->status}; no he hecho cambios.");
                        }
                    }
                }

                return response('ok');
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
        }

        return response('ok');
    }

    // ── 4. Helpers — envío de mail y resumen de estado ──────────────────────

    /**
     * EN: Sends a Mailable catching errors (must not break the webhook).
     *     $cc: extra attendee emails to put in copy (empty array = no Cc), so the
     *     invitees also receive the confirmation/rejection of the booking.
     * ES: Envía un Mailable capturando errores (no debe romper el webhook).
     *     $cc: correos de asistentes extra a poner en copia (array vacío = sin Cc),
     *     para que los invitados reciban también la confirmación/rechazo de la cita.
     */
    protected function enviarMail(string $to, $mailable, array $cc = [], ?string $locale = null): void
    {
        try {
            $mail = Mail::to($to)->cc($cc);
            // EN: If the booking stored the client's language, send the email in it.
            // ES: Si la cita guardó el idioma del cliente, enviamos el correo en ese idioma.
            if ($locale) {
                $mail->locale($locale);
            }
            $mail->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook: fallo al enviar email: ' . $e->getMessage());
        }
    }

    /**
     * EN: Short booking summary to reflect the new status in the message. Formats
     *     the date in the booking's own locale (falls back to the default locale).
     * ES: Texto resumido de la cita para reflejar el nuevo estado en el mensaje.
     *     Formatea la fecha en el idioma propio de la cita (cae al locale por defecto).
     */
    protected function resumen(Appointment $cita, string $estado): string
    {
        // EN: Use the booking locale so the date matches the client's language.
        // ES: Usamos el locale de la cita para que la fecha salga en su idioma.
        $locale = $cita->locale ?? config('appointments.default_locale', 'es');
        $fecha = $cita->date->locale($locale)->isoFormat('dddd D [de] MMMM');

        return "🗓 <b>Cita</b> · <code>{$cita->reference}</code>\n\n"
            . "👤 <b>" . e($cita->name) . "</b>\n"
            . "📅 {$fecha} a las <b>{$cita->time}</b>\n\n"
            . $estado;
    }
}
