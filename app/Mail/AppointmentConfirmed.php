<?php

namespace App\Mail;

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
    use Queueable, SerializesModels;

    // 1. ES: Recibe la cita para mostrar sus detalles.
    //    EN: Receives the appointment to render its details.
    public function __construct(public Appointment $appointment)
    {
        //
    }

    // 2. ES: Asunto traducido según el locale activo (idioma del cliente). El
    //    Reply-To apunta al dueño de la marca para que el cliente pueda responder.
    //    EN: Subject translated by the active locale (client's language). Reply-To
    //    points to the brand owner so the client can answer directly.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.subject_appointment_confirmed'),
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
                'icsUrl' => url('/cita/'.$this->appointment->reference.'/calendario.ics'),
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
