<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentRejected — Cita RECHAZADA al CLIENTE
|==============================================================================
| EN: Sent to the CLIENT when the owner cannot take the appointment, with the
|     reason they write. Explains it is not possible + the reason + signature.
| ES: Se envía al CLIENTE cuando el dueño no puede atender la cita, con el motivo
|     que escribe. Explica que no es posible + el motivo + la firma.
|
| INDEX / ÍNDICE
|   1. __construct() ... receives Appointment + reason / recibe la cita + motivo
|   2. envelope() ...... subject + Reply-To (brand) / asunto + Reply-To (marca)
|   3. content() ....... HTML view / vista HTML
|   4. attachments() ... none / ninguno
|
| NOTE / NOTA:
|   EN: ShouldQueue → the send is queued automatically. Reply-To and branding come
|       from config; no personal data is hardcoded.
|   ES: ShouldQueue → el envío se encola automáticamente. El Reply-To y la marca
|       salen de config; no se hardcodea ningún dato personal.
|==============================================================================
*/
class AppointmentRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // 1. EN: $motivo = the owner's explanation of why they cannot take it.
    //    ES: $motivo = explicación del dueño de por qué no puede atenderla.
    public function __construct(
        public Appointment $appointment,
        public string $motivo,
    ) {
        //
    }

    // 2. EN: Subject translated by the active locale (client's language). Reply-To
    //    points to the brand owner so the client can answer directly.
    //    ES: Asunto traducido según el locale activo (idioma del cliente). El
    //    Reply-To apunta al dueño de la marca para que el cliente pueda responder.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.subject_appointment_rejected'),
            replyTo: [new Address(
                config('appointments.brand.owner_email'),
                config('appointments.brand.owner_name') . ' - ' . config('appointments.brand.name'),
            )],
        );
    }

    // 3. EN: HTML view. The reason ($motivo) and reference are rendered in the
    //    template with Blade variables.
    //    ES: Vista HTML. El motivo ($motivo) y la referencia se montan en la
    //    plantilla con variables Blade.
    public function content(): Content
    {
        return new Content(view: 'emails.appointments.rejected');
    }

    // 4. EN: No attachments. / ES: Sin adjuntos.
    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
