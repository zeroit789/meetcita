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
| AppointmentChangedToOwner — aviso al DUEÑO de un cambio del cliente
|==============================================================================
| ES: Se envía al DUEÑO cuando el cliente, con su enlace de «gestionar mi
|     cita», la CANCELA ($cambio = 'cancelada') o la cambia de día u hora
|     ($cambio = 'reprogramada', con la fecha y hora anteriores). En el idioma
|     por defecto del negocio, como el resto de correos al dueño.
| EN: Sent to the OWNER when the client, with their "manage my booking"
|     link, CANCELS it ($cambio = 'cancelada') or moves it to another day or
|     time ($cambio = 'reprogramada', with the previous date and time). In the
|     business default language, like the other owner emails.
|==============================================================================
*/
class AppointmentChangedToOwner extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * ES: La cita (ya con su estado nuevo), el tipo de cambio y, si se
     *     reprogramó, la fecha ("YYYY-MM-DD") y hora ("HH:MM") de antes.
     * EN: The booking (already in its new state), the kind of change and, if
     *     rescheduled, the previous date ("YYYY-MM-DD") and time ("HH:MM").
     */
    public function __construct(
        public Appointment $appointment,
        public string $cambio,
        public ?string $fechaAnterior = null,
        public ?string $horaAnterior = null,
    ) {}

    /**
     * ES: Asunto en el idioma del dueño con la referencia y el nombre.
     * EN: Subject in the owner's language with the reference and the name.
     */
    public function envelope(): Envelope
    {
        $loc = config('appointments.default_locale', 'es');
        $clave = $this->cambio === 'cancelada' ? 'emails.subject_owner_cancelled' : 'emails.subject_owner_rescheduled';

        return new Envelope(
            subject: __($clave, ['reference' => $this->appointment->reference, 'name' => $this->appointment->name], $loc),
        );
    }

    /**
     * ES: Vista HTML con la cita y, si se reprogramó, de dónde venía.
     * EN: HTML view with the booking and, if rescheduled, where it came from.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.owner-change',
            with: ['panelUrl' => route('panel.index')],
        );
    }
}
