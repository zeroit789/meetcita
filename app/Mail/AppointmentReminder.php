<?php

namespace App\Mail;

use App\Mail\Concerns\EnlaceGestionSoloTitular;
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
| AppointmentReminder — recordatorio al CLIENTE la víspera
|==============================================================================
| ES: Lo envía la orden citas:recordar el día antes de una cita confirmada,
|     en el idioma del cliente (->locale() al enviar). Lleva el resumen, el
|     enlace de Meet si es online, «Añadir al calendario» (.ics firmado) y el
|     enlace firmado para cambiarla o cancelarla. Reply-To al dueño.
| EN: Sent by the citas:recordar command the day before a confirmed booking,
|     in the client's language (->locale() when sending). It carries the
|     summary, the Meet link if online, "Add to calendar" (signed .ics) and
|     the signed link to change or cancel it. Reply-To the owner.
|==============================================================================
*/
class AppointmentReminder extends Mailable implements ShouldQueue
{
    use EnlaceGestionSoloTitular, Queueable, SerializesModels;

    // ES: true en la copia para los asistentes extra: el mismo correo SIN el
    //     enlace de «gestionar mi cita», que es solo del titular. Propiedad
    //     normal con valor por defecto: un correo ya en cola antes de
    //     actualizar se deserializa sin ella (y lleva el enlace, como antes).
    // EN: true on the copy for the extra attendees: the same email WITHOUT
    //     the "manage my booking" link, which belongs to the holder only.
    //     Plain property with a default: an email queued before upgrading is
    //     unserialized without it (and carries the link, as before).
    public bool $paraAsistentes = false;

    /**
     * ES: Recibe la cita que se recuerda (y si es la copia de los asistentes).
     * EN: Receives the booking being reminded (and whether it is the attendees' copy).
     */
    public function __construct(public Appointment $appointment, bool $paraAsistentes = false)
    {
        $this->paraAsistentes = $paraAsistentes;
    }

    /**
     * ES: Asunto traducido (idioma del cliente) con la referencia y la hora;
     *     Reply-To al dueño para que el cliente pueda responder.
     * EN: Translated subject (client's language) with the reference and the
     *     time; Reply-To the owner so the client can answer.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.subject_reminder', [
                'reference' => $this->appointment->reference,
                'time' => $this->appointment->time,
            ]),
            replyTo: [new Address(
                config('appointments.brand.owner_email'),
                config('appointments.brand.owner_name').' - '.config('appointments.brand.name'),
            )],
        );
    }

    /**
     * ES: Vista HTML con los enlaces de calendario y de gestionar (firmados).
     * EN: HTML view with the calendar and manage links (signed).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.reminder',
            with: [
                'googleCalendarUrl' => $this->appointment->urlGoogleCalendar(),
                'icsUrl' => $this->appointment->urlIcs(),
                // ES: Nunca en la copia de los asistentes. EN: Never on the attendees' copy.
                'gestionarUrl' => $this->enlaceGestionSoloTitular(),
            ],
        );
    }
}
