<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentRequestedToOwner — Email al DUEÑO (nueva solicitud de cita)
|==============================================================================
| EN: Sent to the business owner (config 'appointments.brand.owner_email')
|     when a client requests an appointment. Shows all the booking data.
| ES: Se envía al dueño del negocio (config 'appointments.brand.owner_email')
|     cuando un cliente solicita una cita. Muestra todos los datos de la reserva.
|
| INDEX / ÍNDICE
|   1. __construct() ... receives the Appointment / recibe la cita
|   2. envelope() ...... subject in the owner's locale / asunto en el idioma del dueño
|   3. content() ....... HTML view / vista HTML
|   4. attachments() ... none / ninguno
|
| NOTE / NOTA:
|   EN: Implements ShouldQueue → Laravel queues the send automatically, so the
|       HTTP request does not wait for SMTP.
|   ES: Implements ShouldQueue → Laravel encola el envío automáticamente, así la
|       petición HTTP no espera al SMTP.
|==============================================================================
*/
class AppointmentRequestedToOwner extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // 1. EN: Receives the freshly created appointment to render its data.
    //    ES: Recibe la cita recién creada para mostrar sus datos en el email.
    public function __construct(public Appointment $appointment)
    {
        //
    }

    // 2. EN: Subject for the owner. Built in the owner's default locale so the
    //    owner always reads it in a consistent language (config default_locale).
    //    ES: Asunto para el dueño. Se construye en el idioma por defecto del dueño
    //    para que siempre lo lea de forma consistente (config default_locale).
    public function envelope(): Envelope
    {
        // EN: Owner's locale = the app/business default locale.
        // ES: Idioma del dueño = el idioma por defecto de la app/negocio.
        $ownerLocale = config('appointments.default_locale', 'es');

        // EN: Long date in the owner's locale (e.g. "16 de junio" / "June 16").
        // ES: Fecha larga en el idioma del dueño (ej: "16 de junio" / "June 16").
        $fecha = $this->appointment->date->locale($ownerLocale)->isoFormat('D MMMM');

        return new Envelope(
            // EN: Translated subject with the client name + date + time.
            // ES: Asunto traducido con el nombre del cliente + fecha + hora.
            subject: __('emails.subject_appointment_owner', [
                'name' => $this->appointment->name,
                'date' => $fecha,
                'time' => $this->appointment->time,
            ], $ownerLocale),
        );
    }

    // 3. EN: HTML view (tables + inline CSS) with the booking data.
    //    ES: Vista HTML (tablas + CSS inline) con los datos de la cita.
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.owner',
        );
    }

    // 4. EN: No attachments. / ES: Sin adjuntos.
    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
