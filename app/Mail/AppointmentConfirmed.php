<?php

namespace App\Mail;

use App\Mail\Concerns\EnlaceGestionSoloTitular;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentConfirmed — Cita CONFIRMADA al CLIENTE
|==============================================================================
| ES: Se envía al CLIENTE cuando el dueño CONFIRMA la cita (desde el panel u otro
|     canal). Incluye el identificador de la cita y la firma de marca.
| EN: Sent to the CLIENT when the owner CONFIRMS the appointment (from the panel
|     or another channel). Includes the booking reference and the brand signature.
|
| INDEX / ÍNDICE
|   1. __construct() ... receives the Appointment / recibe la cita
|   2. envelope() ...... subject + Reply-To (brand) / asunto + Reply-To (marca)
|   3. content() ....... HTML view + calendar URLs / vista HTML + URLs de calendario
|   4. attachments() ... none / ninguno
|
| NOTE / NOTA:
|   ES: ShouldQueue → el envío se encola automáticamente. El Reply-To y la marca
|       salen de config; no se hardcodea ningún dato personal.
|   EN: ShouldQueue → the send is queued automatically. Reply-To and branding come
|       from config; no personal data is hardcoded.
|==============================================================================
*/
class AppointmentConfirmed extends Mailable implements ShouldQueue
{
    use EnlaceGestionSoloTitular, Queueable, SerializesModels;

    // 1. ES: Recibe la cita para mostrar sus detalles.
    //    EN: Receives the appointment to render its details.
    // ES: true en la copia para los asistentes extra: el mismo correo SIN el
    //     enlace de «gestionar mi cita», que es solo del titular. Propiedad
    //     normal con valor por defecto: un correo ya en cola antes de
    //     actualizar se deserializa sin ella (y lleva el enlace, como antes).
    // EN: true on the copy for the extra attendees: the same email WITHOUT
    //     the "manage my booking" link, which belongs to the holder only.
    //     Plain property with a default: an email queued before upgrading is
    //     unserialized without it (and carries the link, as before).
    public bool $paraAsistentes = false;

    // ES: Revisión de la cita al confirmarla. El correo va en cola y el
    //     worker recarga la cita: si para entonces su revisión es otra (se
    //     reprogramó o se canceló), este correo ya no es verdad y no se envía
    //     (ver AppServiceProvider, MessageSending). Null en correos encolados
    //     antes de actualizar: esos salen como antes.
    // EN: The booking's revision when it was confirmed. The email is queued
    //     and the worker reloads the booking: if by then its revision is
    //     another (rescheduled or cancelled), this email is no longer true and
    //     isn't sent (see AppServiceProvider, MessageSending). Null on emails
    //     queued before upgrading: those go out as before.
    public ?int $revisionEsperada = null;

    public function __construct(public Appointment $appointment, bool $paraAsistentes = false)
    {
        $this->paraAsistentes = $paraAsistentes;
        $this->revisionEsperada = (int) $appointment->revision;
    }

    // 2. ES: Asunto traducido según el locale activo (idioma del cliente). El
    //    Reply-To apunta al dueño de la marca para que el cliente pueda responder.
    //    EN: Subject translated by the active locale (client's language). Reply-To
    //    points to the brand owner so the client can answer directly.
    public function envelope(): Envelope
    {
        return new Envelope(
            // ES: La referencia va en el asunto, igual que en los demás emails.
            // EN: The reference goes in the subject, like in the other emails.
            subject: __('emails.subject_appointment_confirmed', ['reference' => $this->appointment->reference]),
            replyTo: [new Address(
                config('appointments.brand.owner_email'),
                config('appointments.brand.owner_name').' - '.config('appointments.brand.name'),
            )],
        );
    }

    // 3. ES: Pasamos a la vista las URLs para "Añadir al calendario". La lógica de
    //    construcción vive en el modelo (reutilizada por ambos emails de cliente):
    //      - $googleCalendarUrl → abre Google Calendar con la cita precargada.
    //      - $icsUrl            → descarga el .ics (Outlook / Apple Calendar).
    //    EN: We pass the "Add to calendar" URLs to the view. The build logic lives
    //    in the model (reused by both client emails):
    //      - $googleCalendarUrl → opens Google Calendar with the booking preloaded.
    //      - $icsUrl            → downloads the .ics (Outlook / Apple Calendar).
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.confirmed',
            with: [
                'googleCalendarUrl' => $this->appointment->urlGoogleCalendar(),
                'icsUrl' => $this->appointment->urlIcs(),   // ES: enlace firmado / EN: signed link
                // ES: Enlace firmado a «gestionar mi cita» (null si está desactivado
                //     y nunca en la copia de los asistentes).
                // EN: Signed "manage my booking" link (null if disabled and
                //     never on the attendees' copy).
                'gestionarUrl' => $this->enlaceGestionSoloTitular(),
                // ES: true = la cita ya no está confirmada o cambió desde que se
                //     encoló: no se envía. El estado se exige siempre (también en
                //     correos sin revisión guardada, o si la revisión se tomó de
                //     una relectura que ya veía un cambio posterior).
                // EN: true = the booking is no longer confirmed or changed since
                //     it was queued: not sent. The status is always required
                //     (also on emails without a stored revision, or if the
                //     revision came from a re-read that already saw a later change).
                'citaCambiada' => $this->appointment->status !== 'confirmada'
                    || ($this->revisionEsperada !== null
                        && (int) $this->appointment->revision !== $this->revisionEsperada),
            ],
        );
    }

    // 4. ES: Sin adjuntos. / EN: No attachments.
    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
