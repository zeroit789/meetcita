<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentConfirmationToClient — Acuse de recibo al CLIENTE
|==============================================================================
| ES: Acuse de recibo que recibe el CLIENTE tras solicitar su cita. Le confirma
|     que la solicitud se ha recibido (fecha + hora) y que se confirmará en
|     breve. Pasa las URLs de "añadir al calendario" (tentativas).
| EN: Confirmation that the CLIENT receives right after requesting a booking.
|     Tells them the request was received (date + time) and will be confirmed
|     shortly. Passes "add to calendar" URLs (tentative).
|
| INDEX / ÍNDICE
|   1. __construct() ... receives the Appointment / recibe la cita
|   2. envelope() ...... subject in the CLIENT's locale / asunto en el idioma del cliente
|   3. content() ....... HTML view + calendar URLs / vista HTML + URLs de calendario
|   4. attachments() ... none / ninguno
|
| NOTE / NOTA:
|   ES: ShouldQueue → el envío se encola automáticamente. El idioma activo lo fija
|       quien envía el correo con ->locale($appointment->locale).
|   EN: ShouldQueue → the send is queued automatically. The active locale is set
|       by whoever sends the mail with ->locale($appointment->locale).
|==============================================================================
*/
class AppointmentConfirmationToClient extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // 1. ES: Recibe la cita recién creada para personalizar el mensaje.
    //    EN: Receives the freshly created appointment to personalise the message.
    public function __construct(public Appointment $appointment)
    {
        //
    }

    // 2. ES: Asunto para el cliente. Traducido según el locale activo (fijado con
    //    ->locale() al enviar), así respeta el idioma del cliente.
    //    EN: Subject for the client. Translated by the active locale (set with
    //    ->locale() when sending), so it respects the client's language.
    public function envelope(): Envelope
    {
        return new Envelope(
            // ES: La referencia va en el asunto, igual que en los demás emails.
            // EN: The reference goes in the subject, like in the other emails.
            subject: __('emails.subject_appointment_received', ['reference' => $this->appointment->reference]),
        );
    }

    // 3. ES: Vista HTML (tablas + CSS inline) del acuse de recibo.
    //    Pasamos también las URLs de "Añadir al calendario" (tentativas: la cita
    //    aún no está confirmada y puede no tener enlace de Meet todavía). La
    //    lógica vive en el modelo, reutilizada por el email de confirmación.
    //    EN: HTML view (tables + inline CSS) of the acknowledgement.
    //    We also pass the "Add to calendar" URLs (tentative: the appointment is
    //    not confirmed yet and may not have a Meet link). The logic lives in the
    //    model, reused by the confirmation email.
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.client',
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
