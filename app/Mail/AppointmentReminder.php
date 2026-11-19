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
    use Queueable, SerializesModels;

    /**
     * ES: Recibe la cita que se recuerda.
     * EN: Receives the booking being reminded.
     */
    public function __construct(public Appointment $appointment) {}

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
                'gestionarUrl' => $this->appointment->urlGestionar(),
            ],
        );
    }
}
